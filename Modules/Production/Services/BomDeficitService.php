<?php

// Modules/Production/Services/BomDeficitService.php
namespace Modules\Production\Services;

use Illuminate\Support\Facades\DB;
use Modules\Production\Models\BomDeficit;
use Modules\Production\Models\BomDeficitTransaction;
use Modules\Production\Models\BomHeader;
use Modules\Inventory\Models\Product\ProductVariant;

class BomDeficitService
{
    /**
     * Repay outstanding for a BOM/variant up to $qty.
     * Returns ['applied' => repaidQty, 'remaining' => qty - repaidQty].
     */
    public function repayIfOutstanding(
        int $bomId,
        int $variantId,
        float $qty,
        ?float $unitCost = null,
        string $refType = 'auto',
        ?int $refId = null,
        ?string $note = null,
        ?int $sourceBomId = null
    ): array {
        if ($qty <= 0) {
            return ['applied' => 0.0, 'remaining' => 0.0];
        }

        return DB::transaction(function () use ($bomId,$variantId,$qty,$unitCost,$refType,$refId,$note,$sourceBomId) {
            // lock deficit row; create baseline if missing
            $def = DB::table('bom_deficits')
                ->where('bom_id',$bomId)
                ->where('product_variant_id',$variantId)
                ->lockForUpdate()
                ->first();

            $out = (float)($def->qty_outstanding ?? 0);
            if ($out <= 0) {
                // nothing to repay
                return ['applied' => 0.0, 'remaining' => $qty];
            }

            $apply = min($qty, $out);

            // insert repay transaction
            $txnId = DB::table('bom_deficit_transactions')->insertGetId([
                'bom_id'             => $bomId,
                'product_variant_id' => $variantId,
                'direction'          => 'repay',
                'qty'                => $apply,
                'unit_cost'          => $unitCost,
                'source_bom_id'      => $sourceBomId,
                'ref_type'           => $refType,
                'ref_id'             => $refId ?? 0,
                'note'               => $note,
                'created_by'         => auth()->id(),
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // upsert or update rollups
            if ($def) {
                DB::table('bom_deficits')
                    ->where('id', $def->id)
                    ->update([
                        'qty_repaid_total' => DB::raw('qty_repaid_total + '.$apply),
                        'qty_outstanding'  => DB::raw('qty_outstanding - '.$apply),
                        'last_txn_id'      => $txnId,
                        'last_txn_at'      => now(),
                        'updated_at'       => now(),
                    ]);
            } else {
                DB::table('bom_deficits')->insert([
                    'bom_id'             => $bomId,
                    'product_variant_id' => $variantId,
                    'qty_borrowed_total' => 0,
                    'qty_repaid_total'   => $apply,
                    'qty_outstanding'    => 0,
                    'last_txn_id'        => $txnId,
                    'last_txn_at'        => now(),
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }

            return ['applied' => $apply, 'remaining' => $qty - $apply];
        });
    }

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
