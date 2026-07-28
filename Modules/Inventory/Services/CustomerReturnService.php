<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\StockEntry;
use Modules\Inventory\Models\StockReturn;
use Modules\Inventory\Models\StockReturnLine;
use Modules\Inventory\Services\StockService;

class CustomerReturnService
{
    public function create(array $hdr, array $lines): StockEntry
    {
        return DB::transaction(function () use ($hdr, $lines) {

            // Build entry header
            $entryData = [
                'store_id'    => $hdr['store_id'],
                'customer_id' => $hdr['customer_id'],
                'supplier_id' => null,
                'entry_type'  => 'cust_return',
                'status'      => 'draft',
                'entry_date'  => $hdr['entry_date'],
                'remarks'     => $hdr['remarks'] ?? null,
                'reference'   => $this->buildReference($hdr),
            ];

            $entry = StockEntry::create($entryData);

            // Lines
            foreach ($lines as &$ln) {
                $ln['qty']       = (float)($ln['qty'] ?? 0);
                $ln['unit_cost'] = (float)($ln['unit_cost'] ?? 0);
            }
            unset($ln);

            $entry->lines()->createMany($lines);

            // Create StockReturn (origin = StockEntry)
            $return = StockReturn::create([
                'return_type'    => 'customer',
                'return_no'      => $this->nextReturnNo(),
                'request_uuid'   => $hdr['request_uuid'] ?? null, // optional
                'store_id'       => $entry->store_id,
                'customer_id'    => $entry->customer_id,
                'supplier_id'    => null,
                'reference_id'   => $entry->id,
                'reference_type' => StockEntry::class,
                'reason'         => $entry->remarks,
                'status'         => 'draft',
            ]);

            // Aggregate StockReturnLines by variant
            $grouped = [];
            foreach ($lines as $ln) {
                $vid = (int)($ln['product_variant_id'] ?? 0);
                if (!$vid) continue;

                $qty  = (float)($ln['qty'] ?? 0);
                $cost = (float)($ln['unit_cost'] ?? 0);

                $grouped[$vid]['qty']        = ($grouped[$vid]['qty'] ?? 0) + $qty;
                $grouped[$vid]['cost_value'] = ($grouped[$vid]['cost_value'] ?? 0) + ($qty * $cost);
            }

            $rows = [];
            foreach ($grouped as $vid => $agg) {
                $qty = (float)$agg['qty'];
                if ($qty <= 0) continue;

                $rows[] = [
                    'stock_return_id'    => $return->id,
                    'product_variant_id' => $vid,
                    'qty'                => $qty,
                    'unit_cost'          => $qty > 0 ? ($agg['cost_value'] / $qty) : 0,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            if ($rows) StockReturnLine::insert($rows);

            return $entry->load(['store','customer','lines.product_variant']);
        });
    }

    public function update(StockReturn $return, array $hdr, array $lines): StockEntry
    {
        return DB::transaction(function () use ($return, $hdr, $lines) {
            $entry = StockEntry::with('lines')->findOrFail($return->reference_id);

            abort_if($entry->status !== 'draft', 400, 'Only draft can be edited');

            $entry->update([
                'store_id'    => $hdr['store_id'],
                'customer_id' => $hdr['customer_id'],
                'entry_date'  => $hdr['entry_date'],
                'remarks'     => $hdr['remarks'] ?? null,
                'reference'   => $hdr['reference'] ?? $this->buildReference($hdr, $entry->id),
            ]);

            // Replace lines
            $entry->lines()->delete();

            foreach ($lines as &$ln) {
                $ln['qty']       = (float)($ln['qty'] ?? 0);
                $ln['unit_cost'] = (float)($ln['unit_cost'] ?? 0);
            }
            unset($ln);

            $entry->lines()->createMany($lines);

            // Sync StockReturn header
            $return->update([
                'store_id'    => $entry->store_id,
                'customer_id' => $entry->customer_id,
                'reason'      => $entry->remarks,
                'status'      => 'draft',
            ]);

            // Replace StockReturnLines
            $return->lines()->delete();

            $grouped = [];
            foreach ($lines as $ln) {
                $vid = (int)($ln['product_variant_id'] ?? 0);
                if (!$vid) continue;

                $qty  = (float)($ln['qty'] ?? 0);
                $cost = (float)($ln['unit_cost'] ?? 0);

                $grouped[$vid]['qty']        = ($grouped[$vid]['qty'] ?? 0) + $qty;
                $grouped[$vid]['cost_value'] = ($grouped[$vid]['cost_value'] ?? 0) + ($qty * $cost);
            }

            $rows = [];
            foreach ($grouped as $vid => $agg) {
                $qty = (float)$agg['qty'];
                if ($qty <= 0) continue;

                $rows[] = [
                    'stock_return_id'    => $return->id,
                    'product_variant_id' => $vid,
                    'qty'                => $qty,
                    'unit_cost'          => $qty > 0 ? ($agg['cost_value'] / $qty) : 0,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }

            if ($rows) StockReturnLine::insert($rows);

            return $entry->load(['store','customer','lines.product_variant']);
        });
    }

    public function approve(StockReturn $return): StockEntry
    {
        return DB::transaction(function () use ($return) {
            $entry = StockEntry::findOrFail($return->reference_id);

            abort_if($entry->entry_type !== 'cust_return', 400, 'Invalid entry type');
            abort_if($entry->status !== 'draft', 400, 'Already approved');

            $entry->update(['status' => 'approved']);
            $return->update(['status' => 'approved']);

            return $entry;
        });
    }

    /**
     * ✅ FIXED: post now accepts StockEntry (matches your controller call).
     */
    public function post(StockEntry $entry): StockEntry
    {
        return DB::transaction(function () use ($entry) {

            $entry->loadMissing('lines');

            abort_if($entry->entry_type !== 'cust_return', 400, 'Invalid entry type');
            abort_if($entry->status !== 'approved', 400, 'Only approved entries can be posted');

            // Stock-in movement
            StockService::postEntry($entry->fresh('lines'));

            $entry->update([
                'status'    => 'posted',
                'posted_by' => auth()->id(),
            ]);

            return $entry->fresh('lines');
        });
    }

    /**
     * Optional helper if you ever want to post by passing the StockReturn.
     */
    public function postFromReturn(StockReturn $return): StockEntry
    {
        $entry = StockEntry::with('lines')->findOrFail($return->reference_id);

        $postedEntry = $this->post($entry);

        $return->update([
            'status'    => 'posted',
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);

        return $postedEntry;
    }

    public function delete(StockReturn $return): array
    {
        return DB::transaction(function () use ($return) {
            $entry = StockEntry::with('lines')->findOrFail($return->reference_id);

            abort_if($entry->status !== 'draft', 400, 'Only draft can be deleted');

            $meta = [
                'stock_return_id' => $return->id,
                'entry_id'        => $entry->id,
                'store_id'        => $entry->store_id,
                'customer_id'     => $entry->customer_id,
                'reference'       => $entry->reference,
                'entry_date'      => $entry->entry_date,
                'lines'           => $entry->lines->map(fn($l)=>[
                    'product_variant_id'=>$l->product_variant_id,
                    'qty'=>(float)$l->qty,
                    'unit_cost'=>$l->unit_cost !== null ? (float)$l->unit_cost : null,
                ])->values()->toArray(),
            ];

            $return->lines()->delete();
            $return->delete();

            $entry->lines()->delete();
            $entry->delete();

            return $meta;
        });
    }

    protected function nextReturnNo(): string
    {
        $seq = (int) StockReturn::where('return_type', 'customer')->max('id') + 1;
        return 'CR-' . date('Y') . '-' . sprintf('%05d', $seq);
    }

    protected function buildReference(array $hdr, ?int $entryId = null): ?string
    {
        $ref = trim((string)($hdr['reference'] ?? ''));

        if (!empty($hdr['sales_delivery_id'])) {
            $sd = (string)$hdr['sales_delivery_id'];
            $prefix = 'SD#'.$sd;
            return $ref ? ($prefix.' | '.$ref) : $prefix;
        }

        return $ref ?: ($entryId ? ('CR-ENTRY#'.$entryId) : null);
    }
}
