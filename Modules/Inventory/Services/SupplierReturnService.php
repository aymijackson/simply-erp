<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\{
    StockIssue,
    StockTransaction,
    StockReturn,
    StockReturnLine
};
use Modules\Inventory\Models\Product\ProductVariant;
use Illuminate\Database\Eloquent\Relations\Relation;

class SupplierReturnService
{
    /**
     * Create supplier return:
     * - StockIssue (issue_type = supp_return) + StockIssueLines
     * - StockReturn (return_type = supplier) + StockReturnLines
     * all inside ONE DB transaction.
     */
    public function create(array $hdr, array $lines): StockIssue
    {
        return DB::transaction(function () use ($hdr, $lines) {
    
            // ✅ If request already created, stop (prevents double-submit duplicates)
            if (!empty($hdr['request_uuid'])) {
                $existing = StockReturn::where('request_uuid', $hdr['request_uuid'])->first();
                if ($existing) {
                    // return the linked issue if you want, otherwise just return a dummy
                    $issue = StockIssue::find($existing->reference_id);
                    return $issue?->load(['fromStore','supplier','lines.variant']) ?? $issue;
                }
            }
    
            // ---------- Stock Issue ----------
            $hdr['issue_type']    = 'supp_return';
            $hdr['status']        = 'draft';
            $hdr['issue_no']      = $hdr['issue_no'] ?? $this->nextIssueNo();
            $hdr['from_store_id'] = $hdr['from_store_id'] ?? ($hdr['store_id'] ?? null);
    
            $issue = StockIssue::create($hdr);
    
            foreach ($lines as &$ln) {
                $ln['qty']       = (float)($ln['qty'] ?? 0);
                $ln['unit_cost'] = (float)($ln['unit_cost'] ?? 0);
                $ln['value']     = $ln['value'] ?? ($ln['qty'] * $ln['unit_cost']);
            }
            unset($ln);
    
            $issue->lines()->createMany($lines);
    
            // ---------- Stock Return ----------
            $return = StockReturn::create([
                'return_type'    => 'supplier',
                'return_no'      => $hdr['return_no'] ?? $this->nextReturnNo(),
                'request_uuid'   => $hdr['request_uuid'], // ✅ DO NOT allow null here
                'store_id'       => $hdr['store_id'] ?? $hdr['from_store_id'] ?? null,
                'supplier_id'    => $hdr['supplier_id'],
                'reference_id'   => $issue->id,
                'reference_type' => StockIssue::class,
                'reason'         => $hdr['reason'] ?? $hdr['remarks'] ?? null,
                'status'         => 'draft',
            ]);
    
            // aggregate return lines by product_variant_id
            $grouped = [];
            foreach ($lines as $ln) {
                $vid = (int)($ln['product_variant_id'] ?? 0);
                if (!$vid) continue;
    
                $qty  = (float)($ln['qty'] ?? 0);
                $cost = (float)($ln['unit_cost'] ?? 0);
    
                $grouped[$vid]['qty'] = ($grouped[$vid]['qty'] ?? 0) + $qty;
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
                    'unit_cost'          => ($agg['cost_value'] / $qty),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ];
            }
    
            if ($rows) StockReturnLine::insert($rows);
    
            return $issue->load(['fromStore','supplier','lines.variant']);
        });
    }



    /** Draft → approved */
    public function approve(StockIssue $issue): void
    {
        abort_if($issue->issue_type !== 'supp_return', 400, 'Invalid issue type');
        abort_if($issue->status !== 'draft', 400, 'Already approved');

        $issue->update([
            'status'      => 'approved',
            'approved_by' => auth()->id(),
        ]);

        // (Optional) also approve linked StockReturn
        StockReturn::where('reference_type', StockIssue::class)
            ->where('reference_id', $issue->id)
            ->update(['status' => 'approved']);
    }

    /**
     * Approved → posted:
     * - creates stock_transactions (stock OUT from store)
     * - decrements ProductVariant stock_quantity
     * - marks StockIssue + StockReturn as posted
     */
    public function post(StockIssue $issue): void
    {
        abort_if($issue->issue_type !== 'supp_return', 400, 'Invalid issue type');
        abort_if($issue->status !== 'approved', 400, 'Not approved');
        abort_if($issue->status === 'posted', 400, 'Already posted');
    
        DB::transaction(function () use ($issue) {
            $issue->loadMissing('lines');
    
            // ✅ get supplier_id from linked stock_return if missing
            if (!$issue->supplier_id) {
                $sr = StockReturn::where('reference_type', StockIssue::class)
                    ->where('reference_id', $issue->id)
                    ->first();
    
                if ($sr?->supplier_id) {
                    $issue->supplier_id = $sr->supplier_id;
                    $issue->save();
                }
            }
    
            // ✅ delete old ledger rows once (idempotent post)
            StockTransaction::where('txable_type', StockIssue::class)
                ->where('txable_id', $issue->id)
                ->delete();
    
            foreach ($issue->lines as $ln) {
                $qty = (float)$ln->qty;
    
                StockTransaction::create([
                    'product_variant_id' => $ln->product_variant_id,
                    'location_store_id'  => $issue->from_store_id,
                    'tx_type'            => 'ISSUE',
                    'qty'                => abs($qty), // ✅ NEGATIVE (stock out)
                    'unit_cost'          => $ln->unit_cost ?? 0,
                    'supplier_id'        => $issue->supplier_id,
                    'txable_type'        => StockIssue::class,
                    'txable_id'          => $issue->id,
                    'tx_date'            => $issue->issue_date ?? $issue->created_at,
                    'posted_at'          => now(),
                ]);
    
                ProductVariant::whereKey($ln->product_variant_id)
                    ->lockForUpdate()
                    ->decrement('stock_quantity', abs($qty));
            }
    
            $issue->update([
                'status'    => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);
    
            StockReturn::where('reference_type', StockIssue::class)
                ->where('reference_id', $issue->id)
                ->update([
                    'status'    => 'posted',
                    'posted_at' => now(),
                    'posted_by' => auth()->id(),
                ]);
        });
    }

    /* ------------ helpers ------------ */

    protected function nextIssueNo(): string
    {
        // You can change prefix to match your style
        $seq = (int) StockIssue::where('issue_type', 'supp_return')->max('id') + 1;
        return 'SR-ISS-' . date('Y') . '-' . sprintf('%05d', $seq);
    }

    protected function nextReturnNo(): string
    {
        $seq = (int) StockReturn::where('return_type', 'supplier')->max('id') + 1;
        return 'SR-' . date('Y') . '-' . sprintf('%05d', $seq);
    }
}
