<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\LocationStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

use Maatwebsite\Excel\Facades\Excel;
use Modules\Inventory\Exports\StockTransferDashboardExport;

class StockTransferDashboardController extends Controller
{
    public function index()
    {
        return view('inventory.stock.transfers.dashboard.index', [
            'stores' => LocationStore::orderBy('name')->get(),
        ]);
    }

    public function data(Request $request)
    {
        $filters = $this->filters($request);

        // If your header table/cols differ, adjust here:
        $hdrTable = 'stock_transfers';
        $lineTable = 'stock_transfer_lines';

        // Base query (headers)
        $hdrBase = DB::table("$hdrTable as t")
            ->when($filters['from'], fn($q) => $q->whereDate('t.created_at', '>=', $filters['from']))
            ->when($filters['to'], fn($q) => $q->whereDate('t.created_at', '<=', $filters['to']))
            ->when($filters['from_store_id'], fn($q) => $q->where('t.from_store_id', $filters['from_store_id']))
            ->when($filters['to_store_id'], fn($q) => $q->where('t.to_store_id', $filters['to_store_id']))
            ->when($filters['status'], fn($q) => $q->where('t.status', $filters['status']));

        // Join lines
        $lineBase = DB::table("$lineTable as l")
            ->join("$hdrTable as t", 't.id', '=', 'l.stock_transfer_id')
            ->when($filters['from'], fn($q) => $q->whereDate('t.created_at', '>=', $filters['from']))
            ->when($filters['to'], fn($q) => $q->whereDate('t.created_at', '<=', $filters['to']))
            ->when($filters['from_store_id'], fn($q) => $q->where('t.from_store_id', $filters['from_store_id']))
            ->when($filters['to_store_id'], fn($q) => $q->where('t.to_store_id', $filters['to_store_id']))
            ->when($filters['status'], fn($q) => $q->where('t.status', $filters['status']));

        // -------------------------
        // KPIs
        // -------------------------
        $totalTransfers = (clone $hdrBase)->count();

        $postedTransfers = (clone $hdrBase)->where('t.status', 'posted')->count();
        $draftTransfers  = (clone $hdrBase)->where('t.status', 'draft')->count();

        $totalLines = (clone $lineBase)->count();

        $totalQtyMoved = (float) (clone $lineBase)->sum('l.qty');

        // value moved (uses line unit_cost * qty if unit_cost exists)
        $totalValueMoved = (float) (clone $lineBase)->selectRaw('COALESCE(SUM(l.qty * COALESCE(l.unit_cost,0)),0) as v')->value('v');

        // -------------------------
        // Charts
        // -------------------------
        $trendFrom = $filters['from'] ?? now()->subDays(29)->toDateString();
        $trendTo   = $filters['to'] ?? now()->toDateString();

        // (A) Transfers trend (count per day)
        $trend = DB::table("$hdrTable as t")
            ->when($filters['from_store_id'], fn($q) => $q->where('t.from_store_id', $filters['from_store_id']))
            ->when($filters['to_store_id'], fn($q) => $q->where('t.to_store_id', $filters['to_store_id']))
            ->when($filters['status'], fn($q) => $q->where('t.status', $filters['status']))
            ->whereBetween(DB::raw('DATE(t.created_at)'), [$trendFrom, $trendTo])
            ->selectRaw('DATE(t.created_at) d, COUNT(*) transfers')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        // (B) Top routes (from → to) by qty moved
        $topRoutes = (clone $lineBase)
            ->join('location_stores as fs', 'fs.id', '=', 't.from_store_id')
            ->join('location_stores as ts', 'ts.id', '=', 't.to_store_id')
            ->selectRaw("CONCAT(fs.name,' → ',ts.name) as label, COALESCE(SUM(l.qty),0) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->limit(10)
            ->get();

        // (C) Outbound qty by from_store
        $byFromStore = (clone $lineBase)
            ->join('location_stores as fs', 'fs.id', '=', 't.from_store_id')
            ->selectRaw("fs.name as label, COALESCE(SUM(l.qty),0) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->limit(10)
            ->get();

        // (D) Inbound qty by to_store
        $byToStore = (clone $lineBase)
            ->join('location_stores as ts', 'ts.id', '=', 't.to_store_id')
            ->selectRaw("ts.name as label, COALESCE(SUM(l.qty),0) as value")
            ->groupBy('label')
            ->orderByDesc('value')
            ->limit(10)
            ->get();

        // -------------------------
        // Tables
        // -------------------------

        // Top moved SKUs (qty)
        $topSkus = (clone $lineBase)
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'l.product_variant_id')
            ->leftJoin('products as p', 'p.id', '=', 'pv.product_id')
            ->selectRaw("pv.sku, p.product_name as name, COALESCE(SUM(l.qty),0) as moved")
            ->groupBy('pv.sku','p.product_name')
            ->orderByDesc('moved')
            ->limit(10)
            ->get();

        // Draft aging (draft transfers older than X days)
        $draftAging = DB::table("$hdrTable as t")
            ->where('t.status', 'draft')
            ->when($filters['from_store_id'], fn($q) => $q->where('t.from_store_id', $filters['from_store_id']))
            ->when($filters['to_store_id'], fn($q) => $q->where('t.to_store_id', $filters['to_store_id']))
            ->selectRaw("
                SUM(CASE WHEN DATEDIFF(NOW(), t.created_at) <= 1 THEN 1 ELSE 0 END) as d_0_1,
                SUM(CASE WHEN DATEDIFF(NOW(), t.created_at) BETWEEN 2 AND 7 THEN 1 ELSE 0 END) as d_2_7,
                SUM(CASE WHEN DATEDIFF(NOW(), t.created_at) BETWEEN 8 AND 30 THEN 1 ELSE 0 END) as d_8_30,
                SUM(CASE WHEN DATEDIFF(NOW(), t.created_at) > 30 THEN 1 ELSE 0 END) as d_30_plus
            ")
            ->first();

        $draftBuckets = collect([
            ['label' => '0-1 days',  'value' => (int)($draftAging->d_0_1 ?? 0)],
            ['label' => '2-7 days',  'value' => (int)($draftAging->d_2_7 ?? 0)],
            ['label' => '8-30 days', 'value' => (int)($draftAging->d_8_30 ?? 0)],
            ['label' => '30+ days',  'value' => (int)($draftAging->d_30_plus ?? 0)],
        ])->values();

        return response()->json([
            'kpis' => [
                'total_transfers'    => (int)$totalTransfers,
                'posted_transfers'   => (int)$postedTransfers,
                'draft_transfers'    => (int)$draftTransfers,
                'total_lines'        => (int)$totalLines,
                'total_qty_moved'    => (float)$totalQtyMoved,
                'total_value_moved'  => (float)$totalValueMoved,
            ],
            'charts' => [
                'trend'         => $trend,
                'top_routes'    => $topRoutes,
                'by_from_store' => $byFromStore,
                'by_to_store'   => $byToStore,
                'draft_buckets' => $draftBuckets,
            ],
            'tables' => [
                'top_skus' => $topSkus,
            ],
            'meta' => [
                'from' => $trendFrom,
                'to'   => $trendTo,
            ],
        ]);
    }

    public function export(Request $request)
    {
        $filters = $this->filters($request);
        $type = $request->get('type', 'excel');

        $payload = $this->data($request)->getData(true);

        if ($type === 'pdf') {
            $pdf = Pdf::loadView('inventory.stock.transfers.dashboard.pdf', [
                'payload' => $payload,
                'filters' => $filters,
            ]);
            return $pdf->download('stock-transfer-dashboard.pdf');
        }

        return Excel::download(new StockTransferDashboardExport($payload), 'stock-transfer-dashboard.xlsx');
    }

    private function filters(Request $request): array
    {
        return [
            'from'          => $request->filled('from') ? $request->from : null,
            'to'            => $request->filled('to') ? $request->to : null,
            'from_store_id' => $request->filled('from_store_id') ? (int)$request->from_store_id : null,
            'to_store_id'   => $request->filled('to_store_id') ? (int)$request->to_store_id : null,
            'status'        => $request->filled('status') ? $request->status : null, // draft|posted
        ];
    }
}
