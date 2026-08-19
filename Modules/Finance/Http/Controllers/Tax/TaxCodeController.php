<?php

namespace Modules\Finance\Http\Controllers\Tax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Models\Tax\TaxCode;
use Modules\Finance\Models\Tax\TaxRate;

class TaxCodeController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->can('finance.tax.codes.view'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $rates = TaxRate::where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'rate', 'tax_type']);

        return view('finance.tax.codes.index', compact('rates'));
    }

    public function datatable(Request $request)
    {
        abort_unless($request->user()->can('finance.tax.codes.view'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $rows = TaxCode::with('rate:id,name,code,rate')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $rows->map(function ($r) {
                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'code' => $r->code,
                    'tax_type' => $r->tax_type,
                    'rate_id' => $r->rate_id,
                    'rate_name' => $r->rate?->name,
                    'rate_code' => $r->rate?->code,
                    'rate' => $r->rate?->rate,
                    'is_reverse_charge' => $r->is_reverse_charge,
                    'is_exempt' => $r->is_exempt,
                    'is_out_of_scope' => $r->is_out_of_scope,
                    'is_active' => $r->is_active,
                    'notes' => $r->notes,
                ];
            })
        ]);
    }

    public function json(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.tax.codes.view'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $row = TaxCode::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json(['ok' => true, 'data' => $row]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('finance.tax.codes.create'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:20',
            'tax_type' => 'required|in:vat,sales_tax',
            'rate_id' => 'nullable|integer',
            'is_reverse_charge' => 'nullable|boolean',
            'is_exempt' => 'nullable|boolean',
            'is_out_of_scope' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $exists = TaxCode::where('company_id', $companyId)
            ->where('code', $data['code'])
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return response()->json(['ok' => false, 'message' => 'Tax code already exists.'], 422);
        }

        if (!empty($data['rate_id'])) {
            TaxRate::where('company_id', $companyId)->where('id', $data['rate_id'])->firstOrFail();
        }

        TaxCode::create([
            'company_id' => $companyId,
            'name' => $data['name'],
            'code' => $data['code'],
            'tax_type' => $data['tax_type'],
            'rate_id' => $data['rate_id'] ?? null,
            'is_reverse_charge' => isset($data['is_reverse_charge']) ? (int)$data['is_reverse_charge'] : 0,
            'is_exempt' => isset($data['is_exempt']) ? (int)$data['is_exempt'] : 0,
            'is_out_of_scope' => isset($data['is_out_of_scope']) ? (int)$data['is_out_of_scope'] : 0,
            'notes' => $data['notes'] ?? null,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);

        return response()->json(['ok' => true, 'message' => 'Tax code created.']);
    }

    public function update(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.tax.codes.update'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $row = TaxCode::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:20',
            'tax_type' => 'required|in:vat,sales_tax',
            'rate_id' => 'nullable|integer',
            'is_reverse_charge' => 'nullable|boolean',
            'is_exempt' => 'nullable|boolean',
            'is_out_of_scope' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $exists = TaxCode::where('company_id', $companyId)
            ->where('code', $data['code'])
            ->where('id', '!=', $row->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return response()->json(['ok' => false, 'message' => 'Tax code already exists.'], 422);
        }

        if (!empty($data['rate_id'])) {
            TaxRate::where('company_id', $companyId)->where('id', $data['rate_id'])->firstOrFail();
        }

        $row->update([
            'name' => $data['name'],
            'code' => $data['code'],
            'tax_type' => $data['tax_type'],
            'rate_id' => $data['rate_id'] ?? null,
            'is_reverse_charge' => isset($data['is_reverse_charge']) ? (int)$data['is_reverse_charge'] : 0,
            'is_exempt' => isset($data['is_exempt']) ? (int)$data['is_exempt'] : 0,
            'is_out_of_scope' => isset($data['is_out_of_scope']) ? (int)$data['is_out_of_scope'] : 0,
            'notes' => $data['notes'] ?? null,
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);

        return response()->json(['ok' => true, 'message' => 'Tax code updated.']);
    }

    public function destroy(Request $request, $id)
    {
        abort_unless($request->user()->can('finance.tax.codes.delete'), 403);

        $companyId = $request->user()->company_id ?? 1;

        $row = TaxCode::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        $row->delete();

        return response()->json(['ok' => true, 'message' => 'Tax code deleted.']);
    }
}