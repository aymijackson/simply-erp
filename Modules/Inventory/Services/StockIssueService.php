<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\{
    StockIssue, StockTransaction, Product\ProductVariant
};

class StockIssueService
{
    public function post(StockIssue $hdr): void
    {
        abort_if($hdr->status !== 'approved', 400, 'Issue already posted');

        DB::transaction(function () use ($hdr) {

            /* ---------- 1. availability check (by variant) ---------- */
            $shortages = collect($hdr->lines)
                ->groupBy('product_variant_id')
                ->map(function ($grp, $variantId) use ($hdr) {
                    $need = $grp->sum('qty');
                    $have = $this->onHand($variantId, $hdr->from_store_id);
                    return $have < $need
                         ? ['sku' => $grp->first()->variant->sku,
                            'have'=> $have, 'need'=> $need]
                         : null;
                })
                ->filter();

            if ($shortages->isNotEmpty()) {
                // build a nice message for the UI
                $msg = $shortages->map(fn($s) =>
                         "{$s['sku']} (have {$s['have']}, need {$s['need']})"
                       )->implode(', ');
                throw ValidationException::withMessages([
                    'qty' => "Insufficient stock for: $msg"
                ]);
            }

            /* ---------- 2. post each line ---------- */
            foreach ($hdr->lines as $ln) {

                $cost = $this->currentUnitCost($ln->product_variant_id,
                                               $hdr->from_store_id);

                // persist cost in line (for audit)
                $ln->update(['unit_cost' => $cost]);

                // ledger – NEGATIVE qty for ISSUE
                StockTransaction::create([
                    'product_variant_id' => $ln->product_variant_id,
                    'location_store_id'  => $hdr->from_store_id,
                    'tx_type'            => 'ISSUE',
                    'qty'                => $ln->qty,          // ★ negative
                    'unit_cost'          =>  $cost,
                    'txable_type'        => get_class($hdr),
                    'txable_id'          => $hdr->id,
                    'tx_date'            => now(),
                    'posted_at'          => now(),
                ]);

                // running balance on master (redundant but fast for UI)
                ProductVariant::whereKey($ln->product_variant_id)
                              ->lockForUpdate()
                              ->decrement('stock_quantity', $ln->qty);
            }

            $hdr->update([
                'status'    => 'posted',
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);
        });
    }

    /* ---------- helpers ---------- */

    protected function currentUnitCost(int $variantId, int $storeId): float
    {
        // try the view first
        if (DB::selectOne("SHOW FULL TABLES LIKE 'v_stock_layers'")) {
            return DB::table('v_stock_layers')
                    ->where('product_variant_id', $variantId)
                    ->where('location_store_id', $storeId)
                    ->orderBy('created_at')  // FIFO
                    ->value('unit_cost') ?? 0;
        }

        // fallback: last ENTRY cost for that variant+store
        return DB::table('stock_transactions')
                ->where('product_variant_id', $variantId)
                ->where('location_store_id', $storeId)
                ->whereIn('tx_type', ['ENTRY','TRANSFER_IN','ADJUST_POS'])
                ->latest('created_at')
                ->value('unit_cost') ?? 0;
    }

    protected function onHand(int $variantId, int $storeId): float
    {
        return DB::table('v_stock_levels')
                 ->where('product_variant_id', $variantId)
                 ->where('location_store_id', $storeId)
                 ->value('qty_on_hand') ?? 0;
    }
}
