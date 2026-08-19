<?php

namespace Modules\Finance\Http\Controllers\Tax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Tax\TaxRate;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class TaxRateController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.tax.rates.view'), 403);

        return view('finance.tax.rates.index');
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.tax.rates.view'), 403);
    
        $companyId = $request->user()->company_id ?? 1;
    
        $query = TaxRate::where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id');
    
        return DataTables::of($query)
    
            ->editColumn('rate', fn($r) => number_format($r->rate, 4))
    
            ->editColumn('effective_from', fn($r) =>
                $r->effective_from
                    ? Carbon::parse($r->effective_from)->format('d-m-Y')
                    : ''
            )
    
            ->editColumn('effective_to', fn($r) =>
                $r->effective_to
                    ? Carbon::parse($r->effective_to)->format('d-m-Y')
                    : ''
            )
    
            ->editColumn('created_at', fn($r) =>
                $r->created_at
                    ? $r->created_at->format('d-m-Y')
                    : ''
            )
    
            ->editColumn('is_compound', fn($r) => $r->is_compound ? 1 : 0)
            ->editColumn('is_active', fn($r) => $r->is_active ? 1 : 0)
    
            ->make(true);
    }

    public function json(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.tax.rates.view'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $row = TaxRate::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['ok' => true, 'data' => $row]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.tax.rates.create'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:30',
            'rate' => 'required|numeric|min:0|max:100',
            'tax_type' => 'required|in:vat,sales_tax',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_compound' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $exists = TaxRate::where('company_id', $companyId)
            ->where('code', $data['code'])
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return response()->json(['ok' => false, 'message' => 'Tax rate code already exists.'], 422);
        }

        TaxRate::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'code' => $data['code'],
            'rate' => round((float)$data['rate'], 4),
            'tax_type' => $data['tax_type'],
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'is_compound' => isset($data['is_compound']) ? (int)$data['is_compound'] : 0,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);

        return response()->json(['ok' => true, 'message' => 'Tax rate created.']);
    }

    public function update(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.tax.rates.update'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $row = TaxRate::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:30',
            'rate' => 'required|numeric|min:0|max:100',
            'tax_type' => 'required|in:vat,sales_tax',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_compound' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $exists = TaxRate::where('company_id', $companyId)
            ->where('code', $data['code'])
            ->where('id', '!=', $row->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return response()->json(['ok' => false, 'message' => 'Tax rate code already exists.'], 422);
        }

        $row->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'rate' => round((float)$data['rate'], 4),
            'tax_type' => $data['tax_type'],
            'effective_from' => $data['effective_from'] ?? null,
            'effective_to' => $data['effective_to'] ?? null,
            'is_compound' => isset($data['is_compound']) ? (int)$data['is_compound'] : 0,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);

        return response()->json(['ok' => true, 'message' => 'Tax rate updated.']);
    }

    public function destroy(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.tax.rates.delete'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $row = TaxRate::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        $row->delete();

        return response()->json(['ok' => true, 'message' => 'Tax rate deleted.']);
    }
}