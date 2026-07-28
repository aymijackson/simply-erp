<?php

namespace Modules\Inventory\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\CRM\Models\Customer;
use Modules\Inventory\Models\Product\ProductVariant;

class StoreStockController extends Controller
{
    public function available(Request $request)
    {
        $request->validate([
            'location_store_id'   => ['required','integer'],
            'product_variant_id'  => ['required','integer'],
        ]);

        $storeId  = (int) $request->location_store_id;
        $variantId = (int) $request->product_variant_id;

        /**
         * ✅ Best: read from your stock view/table that already represents on-hand by store+variant.
         * Replace `v_stock_levels` + column names to match your system.
         */
        $available = (float) (DB::table('v_stock_levels')
            ->where('location_store_id', $storeId)
            ->where('product_variant_id', $variantId)
            ->value('qty_on_hand') ?? 0);

        return response()->json([
            'available' => max(0, $available),
        ]);
    }
}
