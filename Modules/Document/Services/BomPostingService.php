<?php

namespace App\Services\Mfg;

use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\{StockTransaction};
use App\Models\Mfg\BomHeader;
use Modules\Inventory\Models\Product\ProductVariant;

class BomPostingService
{
    public function approve(BomHeader $bom,int $storeId,float $produceQty): void
    {
        DB::transaction(function() use ($bom,$storeId,$produceQty){

            /* 1. availability check */
            $short = collect();
            foreach ($bom->items as $it){
                $need = $it->qty_per_parent * $produceQty / $bom->yield_qty;
                $have = DB::table('v_stock_levels')
                          ->where('product_variant_id',$it->component_variant_id)
                          ->where('location_store_id',$storeId)
                          ->value('qty_on_hand') ?? 0;

                if($have < $need){
                    $sku  = $it->component->sku;
                    $short->push("$sku need {$need}, have {$have}");
                }
            }
            if($short->isNotEmpty()) abort(422,'Insufficient stock: '.$short->implode('; '));

            /* 2. post transactions & update balances */
            foreach ($bom->items as $it){
                $qty = - ($it->qty_per_parent * $produceQty / $bom->yield_qty); // ISSUE = negative

                StockTransaction::create([
                    'product_variant_id'=>$it->component_variant_id,
                    'location_store_id' =>$storeId,
                    'tx_type'           =>'ISSUE',
                    'qty'               =>$qty,
                    'unit_cost'         => $this->latestCost($it->component_variant_id,$storeId),
                    'txable_type'       => BomHeader::class,
                    'txable_id'         => $bom->id,
                    'tx_date'           => now(),
                    'posted_at'         => now(),
                ]);

                ProductVariant::whereKey($it->component_variant_id)
                              ->lockForUpdate()
                              ->decrement('stock_quantity', abs($qty));
            }

            /* 3. mark BOM approved for this revision */
            $bom->update(['status'=>'approved']);
        });
    }

    protected function latestCost(int $variantId,int $storeId): float
    {
        return DB::table('v_stock_layers')
                 ->where('product_variant_id',$variantId)
                 ->where('location_store_id',$storeId)
                 ->orderByDesc('id')
                 ->value('unit_cost') ?? 0;
    }
}
