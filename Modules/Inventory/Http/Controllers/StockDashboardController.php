<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LocationStore;
use Modules\Inventory\Models\StockTransaction;
use Modules\Inventory\Models\Product\ProductVariant;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Exports\StockDashboardExport;

class StockDashboardController extends Controller
{
    public function index()
    {
        return view('inventory.stock.dashboard.index', [
            'stores' => LocationStore::orderBy('name')->get(),
        ]);
    }

    public function data(Request $request)
    {
        $filters = $this->filters($request);

        // =========================
        // 1) Base query helper (transactions for trend/movers)
        // =========================
        $txBase = StockTransaction::query()
            ->when($filters['store_id'], fn($q) => $q->where('location_store_id', $filters['store_id']))
            ->when($filters['from'], fn($q) => $q->whereDate('created_at', '>=', $filters['from']))
            ->when($filters['to'], fn($q) => $q->whereDate('created_at', '<=', $filters['to']));

        // =========================
        // 2) KPIs (CURRENT STATE from v_stock_levels)
        // =========================

        // Total variants (optionally filter by store: variants that have activity)
        $totalVariants = ProductVariant::query()
            ->when($filters['store_id'], function ($q) use ($filters) {
                $q->whereHas('stockTransactions', fn($t) => $t->where('location_store_id', $filters['store_id']));
            })
            ->count();

        $totalQtyOnHand = (float) DB::table('v_stock_levels')
            ->when($filters['store_id'], fn($q) => $q->where('location_store_id', $filters['store_id']))
            ->sum('qty_on_hand');

        $totalStockValue = (float) DB::table('v_stock_levels')
            ->when($filters['store_id'], fn($q) => $q->where('location_store_id', $filters['store_id']))
            ->sum('value_on_hand');

        // =========================
        // 3) Charts
        // =========================

        // (A) Net movement trend (daily) - transactions (your current logic)
        $trendFrom = $filters['from'] ?? now()->subDays(29)->toDateString();
        $trendTo   = $filters['to'] ?? now()->toDateString();

        $trend = StockTransaction::query()
            ->when($filters['store_id'], fn($q) => $q->where('location_store_id', $filters['store_id']))
            ->whereBetween(DB::raw('DATE(created_at)'), [$trendFrom, $trendTo])
            ->selectRaw('DATE(created_at) d, COALESCE(SUM(qty),0) net')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // (B1) On hand by store (QTY) - from v_stock_levels
        $byStoreQty = DB::table('v_stock_levels as v')
            ->join('location_stores as ls', 'ls.id', '=', 'v.location_store_id')
            ->selectRaw('ls.name as label, COALESCE(SUM(v.qty_on_hand),0) as value')
            ->when($filters['store_id'], fn($q) => $q->where('v.location_store_id', $filters['store_id']))
            ->groupBy('ls.name')
            ->orderByDesc('value')
            ->get();

        // (B2) On hand by store (VALUE) - from v_stock_levels
        $byStoreValue = DB::table('v_stock_levels as v')
            ->join('location_stores as ls', 'ls.id', '=', 'v.location_store_id')
            ->selectRaw('ls.name as label, COALESCE(SUM(v.value_on_hand),0) as value')
            ->when($filters['store_id'], fn($q) => $q->where('v.location_store_id', $filters['store_id']))
            ->groupBy('ls.name')
            ->orderByDesc('value')
            ->get();

        // (C) Top movers (7 days) by variant (absolute movement)
        $movers = StockTransaction::query()
            ->when($filters['store_id'], fn($q) => $q->where('location_store_id', $filters['store_id']))
            ->whereDate('created_at', '>=', now()->subDays(7))
            ->selectRaw('product_variant_id, SUM(ABS(qty)) moved')
            ->groupBy('product_variant_id')
            ->orderByDesc('moved')
            ->limit(10)
            ->with('product_variant:id,sku,product_id', 'product_variant.product:id,product_name')
            ->get()
            ->map(fn($r) => [
                'sku'   => $r->product_variant?->sku,
                'name'  => $r->product_variant?->product?->product_name,
                'moved' => (float) $r->moved,
            ]);

        // =========================
        // 4) Tables (alerts)
        // =========================
        // NOTE: These still use transactions; if you want them transfer-safe,
        // we should rewrite them to use v_stock_levels as well.

        $lowStock = DB::table('product_variants as pv')
                    ->leftJoin('v_stock_levels as v', 'v.product_variant_id', '=', 'pv.id')
                    ->when($filters['store_id'], fn($q) => $q->where('v.location_store_id', $filters['store_id']))
                    ->selectRaw('pv.id, pv.sku, pv.reorder_point, COALESCE(SUM(v.qty_on_hand),0) on_hand')
                    ->groupBy('pv.id','pv.sku','pv.reorder_point')
                    ->havingRaw('pv.reorder_point IS NOT NULL AND COALESCE(SUM(v.qty_on_hand),0) <= pv.reorder_point')
                    ->orderByRaw('COALESCE(SUM(v.qty_on_hand),0) ASC')
                    ->limit(10)
                    ->get();

        $aging = DB::table('product_variants as pv')
            ->leftJoin('stock_transactions as st', 'st.product_variant_id', '=', 'pv.id')
            ->when($filters['store_id'], fn($q) => $q->where('st.location_store_id', $filters['store_id']))
            ->selectRaw('pv.id, pv.sku, MAX(st.created_at) last_move_at, COALESCE(SUM(st.qty),0) on_hand')
            ->groupBy('pv.id','pv.sku')
            ->havingRaw('COALESCE(SUM(st.qty),0) > 0')
            ->get()
            ->map(function ($r) {
                $days = $r->last_move_at ? now()->diffInDays($r->last_move_at) : 9999;
                $bucket = match (true) {
                    $days <= 30 => '0-30',
                    $days <= 60 => '31-60',
                    $days <= 90 => '61-90',
                    $days <= 180 => '91-180',
                    default => '180+',
                };
                return [
                    'sku'          => $r->sku,
                    'on_hand'      => (float) $r->on_hand,
                    'last_move_at' => $r->last_move_at,
                    'bucket'       => $bucket,
                ];
            });

        $agingBuckets = collect(['0-30','31-60','61-90','91-180','180+'])
            ->map(fn($b) => ['label' => $b, 'value' => (int) collect($aging)->where('bucket', $b)->count()])
            ->values();

        return response()->json([
            'kpis' => [
                'total_variants'     => (int) $totalVariants,
                'total_qty_on_hand'  => (float) $totalQtyOnHand,
                'total_stock_value'  => (float) $totalStockValue,
            ],
            'charts' => [
                'trend'          => $trend,
                'by_store_qty'   => $byStoreQty,
                'by_store_value' => $byStoreValue,
                'aging_buckets'  => $agingBuckets,
            ],
            'tables' => [
                'top_movers' => $movers,
                'low_stock'  => $lowStock,
            ],
            'meta' => [
                'from' => $trendFrom,
                'to'   => $trendTo,
            ]
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $type = $request->get('type', 'excel');

        $payload = $this->data($request)->getData(true);

        if ($type === 'pdf') {
            $pdf = Pdf::loadView('inventory.stock.dashboard.pdf', [
                'payload' => $payload,
                'filters' => $filters,
            ]);
            return $pdf->download('stock-dashboard.pdf');
        }

        return Excel::download(new StockDashboardExport($payload), 'stock-dashboard.xlsx');
    }

    private function filters(Request $request): array
    {
        return [
            'from'     => $request->filled('from') ? $request->from : null,
            'to'       => $request->filled('to') ? $request->to : null,
            'store_id' => $request->filled('store_id') ? (int) $request->store_id : null,
        ];
    }
}
