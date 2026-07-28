<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\LocationStore;
use Modules\Inventory\Models\StockEntry;
use Modules\Inventory\Models\StockEntryLine;
use Modules\Inventory\Models\Product\ProductVariant;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Modules\Inventory\Exports\StockEntryAnalyticsExport;

class StockEntryAnalyticsController extends Controller
{
    public function index()
    {
        return view('inventory.stock.entries.analytics', [
            'stores'   => LocationStore::orderBy('name')->get(),
            'variants' => ProductVariant::with('product:id,product_name')->orderBy('sku')->get(),
        ]);
    }

    public function data(Request $request)
    {
        $q = StockEntry::query();
    
        // ---------------- Filters ----------------
        if ($request->filled('from')) {
            $q->whereDate('entry_date', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $q->whereDate('entry_date', '<=', $request->to);
        }
        if ($request->filled('store_id')) {
            $q->where('store_id', $request->store_id);
        }
        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('entry_type')) {
            $q->where('entry_type', $request->entry_type);
        }
    
        // ---------------- KPIs ----------------
        $kpis = [
            'total'    => (clone $q)->count(),
            'draft'    => (clone $q)->where('status','draft')->count(),
            'approved' => (clone $q)->where('status','approved')->count(),
            'posted'   => (clone $q)->where('status','posted')->count(),
        ];
    
        // ---------------- Trend ----------------
        $trendRaw = (clone $q)
            ->selectRaw('DATE(entry_date) as d, COUNT(*) as c')
            ->groupBy('d')
            ->orderBy('d')
            ->get();
    
        // ---------------- By Status ----------------
        $byStatusRaw = (clone $q)
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->get();
    
        // ---------------- By Store ----------------
        $byStoreRaw = (clone $q)
            ->join('location_stores','location_stores.id','=','stock_entries.store_id')
            ->selectRaw('location_stores.name as store, COUNT(*) as c')
            ->groupBy('location_stores.name')
            ->get();
    
        // ---------------- Top Variants ----------------
        $topVariantsRaw = StockEntryLine::query()
            ->join('stock_entries','stock_entries.id','=','stock_entry_lines.stock_entry_id')
            ->join('product_variants','product_variants.id','=','stock_entry_lines.product_variant_id')
            ->join('products','products.id','=','product_variants.product_id')
            ->when($request->filled('store_id'), fn($qq) =>
                $qq->where('stock_entries.store_id',$request->store_id)
            )
            ->selectRaw("CONCAT(product_variants.sku,' – ',products.product_name) as label, SUM(stock_entry_lines.qty) as qty")
            ->groupBy('label')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();
    
        return response()->json([
            'meta' => [
                'range_label' => trim(($request->from ?? 'Start').' → '.($request->to ?? 'Now')),
            ],
            'kpis' => $kpis,
    
            'trend' => [
                'labels' => $trendRaw->pluck('d'),
                'values' => $trendRaw->pluck('c'),
            ],
    
            'by_status' => [
                'labels' => $byStatusRaw->pluck('status')->map(fn($s)=>ucfirst($s)),
                'values' => $byStatusRaw->pluck('c'),
            ],
    
            'by_store' => [
                'labels' => $byStoreRaw->pluck('store'),
                'values' => $byStoreRaw->pluck('c'),
            ],
    
            'top_variants' => [
                'labels' => $topVariantsRaw->pluck('label'),
                'values' => $topVariantsRaw->pluck('qty'),
            ],
        ]);
    }


    public function export(Request $request)
    {
        $type = $request->get('type');

        if ($type === 'excel') {
            $this->middleware('permission:inventory.stock_entries.analytics.export_excel');
        }
        if ($type === 'pdf') {
            $this->middleware('permission:inventory.stock_entries.analytics.export_pdf');
        }

        $payload = $this->data($request)->getData(true);

        if ($type === 'excel') {
            return Excel::download(new StockEntryAnalyticsExport($payload), 'stock-entry-analytics.xlsx');
        }

        if ($type === 'pdf') {
            $pdf = Pdf::loadView('inventory.stock.entries.analytics_pdf', ['data' => $payload]);
            return $pdf->download('stock-entry-analytics.pdf');
        }

        return back()->with('error', 'Unknown export type');
    }
}
