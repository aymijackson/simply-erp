<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\{StockTransfer, StockTransferLine, StockTransaction};
use Modules\Inventory\Models\Product\ProductVariant;

class TransferService
{
    /* ---------- public API ---------- */

    public function create(array $hdr, array $lines): StockTransfer
    {
        return DB::transaction(function () use ($hdr, $lines) {
            $hdr += [
                'transfer_no' => $hdr['transfer_no'] ?? $this->nextNo(),
                'status'      => 'draft',
            ];

            $trf = StockTransfer::create($hdr);

            // Ensure unit_cost is set on each line (if not provided)
            $lines = $this->hydrateUnitCostLines(
                fromStoreId: (int) ($hdr['from_store_id'] ?? $trf->from_store_id),
                lines: $lines
            );

            $trf->lines()->createMany($lines);

            return $trf->load('lines.variant');
        });
    }

    public function updateDraft(StockTransfer $trf, array $hdr, array $lines): StockTransfer
    {
        abort_if($trf->status !== 'draft', 400, 'Only draft can be updated');
    
        return DB::transaction(function () use ($trf, $hdr, $lines) {
            $trf->update($hdr);
    
            // Replace lines (simple + safe)
            $trf->lines()->delete();
            $trf->lines()->createMany($lines);
    
            return $trf->load('lines.variant');
        });
    }
    
    public function post(StockTransfer $trf): void
    {
        abort_if($trf->status !== 'draft', 400, 'Already posted');

        DB::transaction(function () use ($trf) {

            /* -------  A  check balances  -------- */
            foreach ($trf->lines as $l) {
                $onHand = DB::table('v_stock_levels')
                    ->where([
                        'product_variant_id' => $l->product_variant_id,
                        'location_store_id'  => $trf->from_store_id,
                    ])
                    ->lockForUpdate()
                    ->value('qty_on_hand') ?? 0;

                if ($onHand < $l->qty) {
                    throw new \RuntimeException(
                        "Insufficient stock for SKU {$l->variant->sku} in {$trf->fromStore->name}. "
                        . "On-hand: {$onHand}, requested: {$l->qty}"
                    );
                }
            }

            /* -------  B  write both legs  -------- */
            foreach ($trf->lines as $l) {
                // OUT
                $this->ledger('TRANSFER_OUT', $trf->from_store_id, $l, -1);
                // IN
                $this->ledger('TRANSFER_IN',  $trf->to_store_id,   $l, +1);
            }

            $trf->update([
                'status'      => 'posted',
                'posted_at'   => now(),
                'approved_by' => auth()->id(),
            ]);
        });
    }

    /* ---------- helpers ---------- */

    /**
     * Fill missing unit_cost for lines using FROM store stock valuation.
     * Keeps your qty sign convention unchanged.
     */
    protected function hydrateUnitCostLines(int $fromStoreId, array $lines): array
    {
        if (!$fromStoreId || empty($lines)) return $lines;

        // Collect variant ids from incoming lines
        $variantIds = collect($lines)
            ->pluck('product_variant_id')
            ->filter()
            ->unique()
            ->values();

        if ($variantIds->isEmpty()) return $lines;

        // 1) Pull valuation from v_stock_levels (value_on_hand / qty_on_hand)
        $valuation = DB::table('v_stock_levels')
            ->where('location_store_id', $fromStoreId)
            ->whereIn('product_variant_id', $variantIds)
            ->select('product_variant_id', 'qty_on_hand', 'value_on_hand')
            ->get()
            ->keyBy('product_variant_id');

        // 2) Fallback: last transaction unit_cost per variant in that store
        // (works even if view doesn't have value_on_hand)
        $lastCosts = DB::table('stock_transactions as st')
            ->where('st.location_store_id', $fromStoreId)
            ->whereIn('st.product_variant_id', $variantIds)
            ->whereNotNull('st.unit_cost')
            ->orderByDesc('st.id')
            ->get(['st.product_variant_id', 'st.unit_cost'])
            ->unique('product_variant_id')
            ->keyBy('product_variant_id');

        // 3) Fallback: variant price (last resort)
        $variantPrice = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->pluck('price', 'id');

        // Apply to each line
        return array_map(function ($line) use ($valuation, $lastCosts, $variantPrice) {

            // If unit_cost is already provided, keep it
            if (isset($line['unit_cost']) && $line['unit_cost'] !== null && $line['unit_cost'] !== '') {
                return $line;
            }

            $vid = (int) ($line['product_variant_id'] ?? 0);
            $unitCost = 0.0;

            if ($vid && isset($valuation[$vid])) {
                $qty = (float) ($valuation[$vid]->qty_on_hand ?? 0);
                $val = (float) ($valuation[$vid]->value_on_hand ?? 0);
                if ($qty > 0) {
                    $unitCost = $val / $qty;
                }
            }

            // fallback to last tx unit_cost
            if ($unitCost <= 0 && $vid && isset($lastCosts[$vid])) {
                $unitCost = (float) ($lastCosts[$vid]->unit_cost ?? 0);
            }

            // last resort: variant price
            if ($unitCost <= 0 && $vid) {
                $unitCost = (float) ($variantPrice[$vid] ?? 0);
            }

            $line['unit_cost'] = $unitCost;

            return $line;
        }, $lines);
    }

    /**
     * Write one ledger row
     */
    protected function ledger(string $type, int $storeId, StockTransferLine $l, int $sign): void
    {
        // derive cost from stock layers (FIFO)
        $unitCost = DB::table('v_stock_layers')
            ->where('product_variant_id', $l->product_variant_id)
            ->where('location_store_id', $l->transfer->from_store_id)
            ->orderBy('created_at')
            ->value('unit_cost');
    
        if ($unitCost === null) {
            throw new \RuntimeException(
                "No stock valuation found for SKU {$l->variant->sku} in store {$storeId}"
            );
        }
    
        StockTransaction::create([
            'product_variant_id' => $l->product_variant_id,
            'location_store_id'  => $storeId,
            'tx_type'            => $type,
            'qty'                => $l->qty, // always positive
            'unit_cost'          => $unitCost ?? 0,
            'txable_type'        => StockTransfer::class,
            'txable_id'          => $l->stock_transfer_id,
            'tx_date'            => now(),
        ]);
    }

    protected function nextNo(): string
    {
        $seq = StockTransfer::max('id') + 1;
        return 'TRF-' . date('Y') . '-' . sprintf('%05d', $seq);
    }
}
