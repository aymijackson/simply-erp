<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LocationStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockLevelsDashboardController extends Controller
{
    public function index()
    {
        return view('inventory.stock.levels.dashboard.index', [
            'stores' => LocationStore::orderBy('name')->get(['id','name']),
        ]);
    }

    public function data(Request $request)
    {
        $storeId   = $request->integer('store_id');
        $variantId = $request->integer('product_variant_id');
        $mode      = $request->get('mode', 'per_store'); // per_store | global
        $inStock   = $request->boolean('in_stock');

        // Base view query
        $v = DB::table('v_stock_levels as v')
            ->when($storeId, fn($q) => $q->where('v.location_store_id', $storeId))
            ->when($variantId, fn($q) => $q->where('v.product_variant_id', $variantId))
            ->when($inStock, fn($q) => $q->where('v.qty_on_hand', '>', 0));

        // KPIs (respect filters)
        $kpiTotalQty = (float) (clone $v)->sum('v.qty_on_hand');

        // value_on_hand optional (if column exists)
        $kpiTotalValue = 0.0;
        try {
            $kpiTotalValue = (float) (clone $v)->sum('v.value_on_hand');
        } catch (\Throwable $e) {
            $kpiTotalValue = 0.0;
        }

        // total variants count (respect filters)
        $kpiVariants = (int) (clone $v)->distinct('v.product_variant_id')->count('v.product_variant_id');

        // Chart: Qty by store (still respects variant filter + inStock, but ignores store filter so it’s meaningful)
        $byStore = DB::table('v_stock_levels as v')
            ->join('location_stores as ls', 'ls.id', '=', 'v.location_store_id')
            ->when($variantId, fn($q) => $q->where('v.product_variant_id', $variantId))
            ->when($inStock, fn($q) => $q->where('v.qty_on_hand', '>', 0))
            ->selectRaw('ls.name as label, COALESCE(SUM(v.qty_on_hand),0) as value')
            ->groupBy('ls.name')
            ->orderByDesc('value')
            ->limit(10)
            ->get();

        // Table: cumulative list
        if ($mode === 'global') {
            $rows = DB::table('v_stock_levels as v')
                ->join('product_variants as pv', 'pv.id', '=', 'v.product_variant_id')
                ->leftJoin('products as p', 'p.id', '=', 'pv.product_id')
                ->when($storeId, fn($q) => $q->where('v.location_store_id', $storeId))
                ->when($variantId, fn($q) => $q->where('v.product_variant_id', $variantId))
                ->when($inStock, fn($q) => $q->where('v.qty_on_hand', '>', 0))
                ->selectRaw("
                    pv.id as product_variant_id,
                    pv.sku as sku,
                    COALESCE(p.product_name,'') as product_name,
                    COALESCE(SUM(v.qty_on_hand),0) as qty_on_hand
                ")
                ->groupBy('pv.id','pv.sku','p.product_name')
                ->orderByDesc('qty_on_hand')
                ->limit(500)
                ->get();
        } else {
            // per_store
            $rows = DB::table('v_stock_levels as v')
                ->join('location_stores as ls', 'ls.id', '=', 'v.location_store_id')
                ->join('product_variants as pv', 'pv.id', '=', 'v.product_variant_id')
                ->leftJoin('products as p', 'p.id', '=', 'pv.product_id')
                ->when($storeId, fn($q) => $q->where('v.location_store_id', $storeId))
                ->when($variantId, fn($q) => $q->where('v.product_variant_id', $variantId))
                ->when($inStock, fn($q) => $q->where('v.qty_on_hand', '>', 0))
                ->selectRaw("
                    v.location_store_id,
                    ls.name as store_name,
                    pv.id as product_variant_id,
                    pv.sku as sku,
                    COALESCE(p.product_name,'') as product_name,
                    COALESCE(v.qty_on_hand,0) as qty_on_hand
                ")
                ->orderByDesc('qty_on_hand')
                ->limit(500)
                ->get();
        }

        return response()->json([
            'kpis' => [
                'total_variants' => $kpiVariants,
                'total_qty_on_hand' => $kpiTotalQty,
                'total_stock_value' => $kpiTotalValue,
            ],
            'charts' => [
                'by_store' => $byStore,
            ],
            'table' => [
                'mode' => $mode,
                'rows' => $rows,
            ],
        ]);
    }
}
