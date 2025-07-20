<?php
// Modules/Inventory/Http/Controllers/AgingController.php
namespace Modules\Inventory\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Inventory\Models\StockAge;
use Modules\Inventory\Models\Product\ProductVariant;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockDashboardController extends Controller
{
    /* ---------- blade wrapper ---------- */
    public function index()
    {
        return view('inventory.stock.dashboard.index');
    }

    /* ---------- KPI cards ---------- */
    public function cards()
    {
        $cards = DB::table('v_stock_levels')
            ->selectRaw('COUNT(DISTINCT product_variant_id)  as variants,
                         SUM(qty_on_hand)                    as total_qty,
                         SUM(value_on_hand)                  as total_value')
            ->first();
        /* format with commas right here */
        $data = [
            'variants'    => number_format($cards->variants),            // 1,234
            'total_qty'   => number_format($cards->total_qty),           // 56,789
            'total_value' => number_format($cards->total_value, 2, '.', ','), // 1,234,567.89
        ];

        return response()->json($data);
    }

    /* ---------- Top 10 movers last 7 days ---------- */
    public function topMovers()
    {
        $rows = DB::table('stock_transactions')
            ->join('product_variants','product_variants.id','=','stock_transactions.product_variant_id')
            ->selectRaw('product_variants.sku,
                         SUM(ABS(qty))  as moved_qty')
            ->whereBetween('tx_date', [now()->subDays(7), now()])
            ->groupBy('sku')
            ->orderByDesc('moved_qty')
            ->limit(10)
            ->get();

        return datatables()
            ->of($rows)
            ->make(true);
    }

    /* ---------- Low stock grid ---------- */
    public function lowStock()
{
    $rows = ProductVariant::query()
        ->join('v_stock_levels as v', 'v.product_variant_id', '=', 'product_variants.id')
        // COALESCE handles NULL reorder_point ⇒ treat as 0
        ->whereRaw('v.qty_on_hand <= COALESCE(product_variants.reorder_point, 0)')
        ->select([
            'product_variants.sku          as sku',
            'product_variants.reorder_point',
            'v.qty_on_hand',
            'v.value_on_hand'
        ])
        ->orderBy('product_variants.sku');

    return datatables()
           ->eloquent($rows)   // works because $rows is now Eloquent\Builder
           ->make(true);


    }

    /* ---------- Aging chart data ---------- */
    public function agingChart()
    {
        $rows = DB::table('v_stock_age')
            ->selectRaw('age_bucket, SUM(qty) as qty')
            ->groupBy('age_bucket')
            ->orderByRaw("FIELD(age_bucket,'0‑30','31‑60','61‑90','91+')")
            ->get();

        return response()->json($rows);
    }
}