<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SupplierAnalyticsController extends Controller
{
    private function audit(string $action, ?string $description = null, $subject = null, array $meta = []): void
    {
        // choose a correct module key for analytics
        $module = 'inventory.suppliers.analytics';

        auth()->user()?->audit(
            module: $module,
            action: $action,
            description: $description,
            subject: $subject,
            meta: $meta
        );
    }

    public function index(Request $request)
    {
        $this->audit('viewed', 'Viewed supplier analytics dashboard', null, [
            'filters' => $request->only(['supplier_id','store_id','date_from','date_to'])
        ]);
        
        
        $this->audit('viewed_products', 'Viewed supplier product-level analytics', null, [
            'min_return_rate' => $minRate ?? null,
        ]);
        
        $this->audit('viewed_reasons', 'Viewed supplier return reason analytics', null, [
        ]);

        return view('suppliers.analytics.index', [
            'suppliers_count' => Supplier::count(),
        ]);
    }

    /**
     * KPI cards for dashboard (supports filters)
     */
    public function kpis(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        $supplierId = $request->get('supplier_id');
        $storeId    = $request->get('store_id');

        // Supply totals
        $supply = DB::table('stock_entries as se')
            ->join('stock_entry_lines as sel', 'sel.stock_entry_id', '=', 'se.id')
            ->when($supplierId, fn($q) => $q->where('se.supplier_id', $supplierId))
            ->when($storeId, fn($q) => $q->where('se.store_id', $storeId))
            ->when($dateFrom, fn($q) => $q->whereDate('se.entry_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('se.entry_date', '<=', $dateTo))
            ->where('se.status', 'posted')
            ->where('se.entry_type', 'normal')
            ->selectRaw('
                COUNT(DISTINCT se.id) as supply_docs,
                COALESCE(SUM(sel.qty),0) as supply_qty,
                COALESCE(SUM(sel.qty * sel.unit_cost),0) as supply_value
            ')
            ->first();

        // Return totals (supplier returns)
        $returns = DB::table('stock_returns as sr')
            ->join('stock_return_lines as srl', 'srl.stock_return_id', '=', 'sr.id')
            ->when($supplierId, fn($q) => $q->where('sr.reference_id', $supplierId))
            ->when($storeId, fn($q) => $q->where('sr.store_id', $storeId))
            ->when($dateFrom, fn($q) => $q->whereDate('sr.created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('sr.created_at', '<=', $dateTo))
            ->where('sr.status', 'posted')
            ->where('sr.return_type', 'supplier')
            ->selectRaw('
                COUNT(DISTINCT sr.id) as return_docs,
                COALESCE(SUM(srl.qty),0) as return_qty,
                COALESCE(SUM(srl.qty * srl.unit_cost),0) as return_value
            ')
            ->first();

        $supplyQty = (float)($supply->supply_qty ?? 0);
        $returnQty = (float)($returns->return_qty ?? 0);

        $returnRate = $supplyQty > 0 ? round(($returnQty / $supplyQty) * 100, 2) : 0;
        $netValue = (float)($supply->supply_value ?? 0) - (float)($returns->return_value ?? 0);
        $qualityScore = max(0, round(100 - $returnRate, 2));

        return response()->json([
            'supply_docs'   => (int)($supply->supply_docs ?? 0),
            'supply_qty'    => $supplyQty,
            'supply_value'  => (float)($supply->supply_value ?? 0),
            'return_docs'   => (int)($returns->return_docs ?? 0),
            'return_qty'    => $returnQty,
            'return_value'  => (float)($returns->return_value ?? 0),
            'net_value'     => $netValue,
            'return_rate'   => $returnRate,
            'quality_score' => $qualityScore,
        ]);
    }

    /**
     * Monthly trend chart (supplies vs returns)
     */
    public function trend(Request $request)
    {
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');
        $supplierId = $request->get('supplier_id');
        $storeId    = $request->get('store_id');

        // supplies trend by month
        $supplies = DB::table('stock_entries as se')
            ->join('stock_entry_lines as sel', 'sel.stock_entry_id', '=', 'se.id')
            ->when($supplierId, fn($q) => $q->where('se.supplier_id', $supplierId))
            ->when($storeId, fn($q) => $q->where('se.store_id', $storeId))
            ->when($dateFrom, fn($q) => $q->whereDate('se.entry_date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('se.entry_date', '<=', $dateTo))
            ->where('se.status', 'posted')
            ->where('se.entry_type', 'normal')
            ->groupByRaw("DATE_FORMAT(se.entry_date, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(se.entry_date, '%Y-%m')")
            ->selectRaw("
                DATE_FORMAT(se.entry_date, '%Y-%m') as ym,
                COALESCE(SUM(sel.qty),0) as qty,
                COALESCE(SUM(sel.qty * sel.unit_cost),0) as value
            ")
            ->get()
            ->keyBy('ym');

        // returns trend by month
        $returns = DB::table('stock_returns as sr')
            ->join('stock_return_lines as srl', 'srl.stock_return_id', '=', 'sr.id')
            ->when($supplierId, fn($q) => $q->where('sr.reference_id', $supplierId))
            ->when($storeId, fn($q) => $q->where('sr.store_id', $storeId))
            ->when($dateFrom, fn($q) => $q->whereDate('sr.created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('sr.created_at', '<=', $dateTo))
            ->where('sr.status', 'posted')
            ->where('sr.return_type', 'supplier')
            ->groupByRaw("DATE_FORMAT(sr.created_at, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(sr.created_at, '%Y-%m')")
            ->selectRaw("
                DATE_FORMAT(sr.created_at, '%Y-%m') as ym,
                COALESCE(SUM(srl.qty),0) as qty,
                COALESCE(SUM(srl.qty * srl.unit_cost),0) as value
            ")
            ->get()
            ->keyBy('ym');

        // merge months
        $months = collect(array_unique(array_merge(
            $supplies->keys()->all(),
            $returns->keys()->all()
        )))->sort()->values();

        $labels = [];
        $supplyQty = [];
        $returnQty = [];
        $supplyValue = [];
        $returnValue = [];

        foreach ($months as $m) {
            $labels[] = $m;
            $supplyQty[] = (float) optional($supplies->get($m))->qty ?? 0;
            $returnQty[] = (float) optional($returns->get($m))->qty ?? 0;
            $supplyValue[] = (float) optional($supplies->get($m))->value ?? 0;
            $returnValue[] = (float) optional($returns->get($m))->value ?? 0;
        }

        return response()->json([
            'labels' => $labels,
            'supply_qty' => $supplyQty,
            'return_qty' => $returnQty,
            'supply_value' => $supplyValue,
            'return_value' => $returnValue,
        ]);
    }

    /**
     * Ranking / summary table
     */
    public function datatable(Request $request)
    {
        $supplierId = $request->get('supplier_id');

        $q = DB::table('v_supplier_kpi')
            ->when($supplierId, fn($qq) => $qq->where('supplier_id', $supplierId));

        return DataTables::of($q)
            ->addColumn('actions', function ($row) {
                return '<a class="btn btn-sm btn-outline-primary" href="'.
                    route('admin.supplier_analytics.show', $row->supplier_id).
                '"><i class="fas fa-chart-line me-1"></i> View</a>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Supplier drill-down page (optional)
     */
    public function show(Request $request, Supplier $supplier)
    {
        $qTerm      = trim((string)$request->get('q', ''));
        $this->audit('viewed_supplier', "Viewed supplier analytics: {$supplier->name}", $supplier, [
            'supplier_id' => $supplier->id
        ]);

        $this->audit('viewed_products', 'Viewed supplier product-level analytics', null, [
            'supplier_id' => $supplier->id,
            'min_return_rate' => $minRate ?? null,
            'q' => $qTerm ?: null,
        ]);
        
        $this->audit('viewed_reasons', 'Viewed supplier return reason analytics', null, [
            'supplier_id' => $supplier->id,
            'q' => $qTerm ?: null,
        ]);
        return view('suppliers.analytics.show', compact('supplier'));
    }
    
    public function productsDatatable(Request $request)
    {
        $supplierId = $request->get('supplier_id');
        $minRate    = $request->get('min_return_rate'); // optional filter
        $qTerm      = trim((string)$request->get('q', ''));
    
        $q = DB::table('v_supplier_product_kpi')
            ->when($supplierId, fn($qq) => $qq->where('supplier_id', $supplierId))
            ->when($minRate !== null && $minRate !== '', fn($qq) => $qq->where('return_rate_pct', '>=', (float)$minRate))
            ->when($qTerm, function($qq) use ($qTerm){
                $qq->where(function($w) use ($qTerm){
                    $w->where('product_name', 'like', "%{$qTerm}%")
                      ->orWhere('product_code', 'like', "%{$qTerm}%")
                      ->orWhere('supplier_name', 'like', "%{$qTerm}%");
                });
            });
    
        return DataTables::of($q)
            ->make(true);
    }
    
   public function reasonsDatatable(Request $request)
    {
        $supplierId = $request->get('supplier_id');
        $qTerm      = trim((string)$request->get('q', ''));
    
        $q = DB::table('v_supplier_return_reasons')
            ->select([
                'reason',
                'return_docs',
                'return_qty',
                'return_value',
                'first_return_at',
                'last_return_at',
                'supplier_id',
            ])
            ->when($supplierId, fn($qq) => $qq->where('supplier_id', $supplierId))
            ->when($qTerm, fn($qq) => $qq->where('reason', 'like', "%{$qTerm}%"));
    
        return DataTables::of($q)
        ->addColumn('first_return_at', function ($row) {
                return date('d-m-Y H:i', strtotime($row->first_return_at));
            })
        ->addColumn('last_return_at', function ($row) {
                return date('d-m-Y H:i', strtotime($row->last_return_at));
            })
        ->make(true);
    }


}
