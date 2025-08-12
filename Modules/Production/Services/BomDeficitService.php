<?php

// Modules/Production/Services/BomDeficitService.php
namespace Modules\Production\Services;

use Illuminate\Support\Facades\DB;
use Modules\Production\Models\BomDeficit;
use Modules\Production\Models\BomDeficitTransaction;

class BomDeficitService
{
    public function recordBorrow(int $borrowerBomId, int $variantId, float $qty, ?int $sourceBomId, ?float $unitCost, $ref): void
    {
        DB::transaction(function () use ($borrowerBomId, $variantId, $qty, $sourceBomId, $unitCost, $ref) {
            $txn = BomDeficitTransaction::create([
                'bom_id'            => $borrowerBomId,
                'product_variant_id'=> $variantId,
                'direction'         => 'borrow',
                'qty'               => $qty,
                'unit_cost'         => $unitCost,
                'source_bom_id'     => $sourceBomId,
                'ref_type'          => get_class($ref),
                'ref_id'            => $ref->getKey(),
                'created_by'        => auth()->id(),
            ]);

            $agg = BomDeficit::firstOrNew(['bom_id'=>$borrowerBomId,'product_variant_id'=>$variantId]);
            $agg->qty_borrowed_total = ($agg->qty_borrowed_total ?? 0) + $qty;
            $agg->qty_outstanding    = ($agg->qty_outstanding ?? 0)    + $qty;
            $agg->last_txn_at = now();
            $agg->last_txn_id = $txn->id;
            $agg->save();
        });
    }

    /**
     * Reconcile automatically when issuing stock to BOM.
     * Returns how much of issue_qty was consumed by reconciliation.
     */
    public function reconcileOnIssue(int $bomId, int $variantId, float $issueQty, ?float $unitCost, $ref): float
    {
        return DB::transaction(function () use ($bomId, $variantId, $issueQty, $unitCost, $ref) {
            $agg = BomDeficit::lockForUpdate()->where('bom_id',$bomId)->where('product_variant_id',$variantId)->first();
            if (!$agg || $agg->qty_outstanding <= 0 || $issueQty <= 0) return 0.0;

            $apply = min($issueQty, (float)$agg->qty_outstanding);

            $txn = BomDeficitTransaction::create([
                'bom_id'            => $bomId,
                'product_variant_id'=> $variantId,
                'direction'         => 'repay',
                'qty'               => $apply,
                'unit_cost'         => $unitCost,
                'ref_type'          => get_class($ref),
                'ref_id'            => $ref->getKey(),
                'created_by'        => auth()->id(),
            ]);

            $agg->qty_repaid_total += $apply;
            $agg->qty_outstanding  -= $apply;
            $agg->last_txn_at = now();
            $agg->last_txn_id = $txn->id;
            $agg->save();

            return $apply;
        });
    }
}
