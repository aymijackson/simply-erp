<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Sales\Models\PricingRule;
use Yajra\DataTables\Facades\DataTables;

/**
 * PricingRuleController
 *
 * ── PERMISSION MAP ───────────────────────────────────────────────────────────
 * index, datatable          sales.pricing_rules.view
 * store                     sales.pricing_rules.create
 * update                    sales.pricing_rules.edit
 * destroy, bulkDelete       sales.pricing_rules.delete
 * toggleActive              sales.pricing_rules.edit
 * ────────────────────────────────────────────────────────────────────────────
 */
class PricingRuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:sales.pricing_rules.view',
            ['only' => ['index', 'datatable']]);
        $this->middleware('permission:sales.pricing_rules.create',
            ['only' => ['store']]);
        $this->middleware('permission:sales.pricing_rules.edit',
            ['only' => ['update', 'toggleActive']]);
        $this->middleware('permission:sales.pricing_rules.delete',
            ['only' => ['destroy', 'bulkDelete']]);
    }

    public function index()
    {
        return view('price_lists.rules');
    }

    public function datatable(Request $request)
    {
        $q = PricingRule::query()
            ->when($request->apply_on,  fn($q, $v) => $q->where('apply_on', $v))
            ->when($request->is_active, fn($q, $v) => $q->where('is_active', (bool) $v))
            ->orderBy('priority')
            ->orderBy('name');

        return DataTables::eloquent($q)
            ->addColumn('status_badge', fn($r) => $r->is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>')
            ->addColumn('discount_display', fn($r) => $r->discount_type === 'percent'
                ? number_format($r->discount_value, 2).'%'
                : number_format($r->discount_value, 2).' off')
            ->addColumn('validity', function ($r) {
                $from = $r->valid_from?->format('d M Y') ?? '—';
                $to   = $r->valid_to?->format('d M Y')   ?? '—';
                return "{$from} → {$to}";
            })
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-rule"
                    data-id="'.$r->id.'" data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                 <button class="btn btn-xs btn-'.($r->is_active ? 'secondary' : 'success').' btn-toggle-rule"
                    data-id="'.$r->id.'" title="'.($r->is_active ? 'Deactivate' : 'Activate').'">
                    <i class="fas fa-power-off"></i></button>
                 <button class="btn btn-xs btn-danger btn-delete-rule" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $rule = PricingRule::create([
            ...$validated,
            'company_id' => auth()->user()->company_id ?? 1,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Rule created.', 'rule' => $rule], 201);
    }

    public function update(Request $request, PricingRule $pricingRule)
    {
        $validated = $request->validate($this->rules());
        $pricingRule->update([...$validated, 'updated_by' => auth()->id()]);

        return response()->json(['message' => 'Rule updated.']);
    }

    public function destroy(PricingRule $pricingRule)
    {
        $pricingRule->delete();
        return response()->json(['message' => 'Rule deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => 'integer']);
        $count = PricingRule::whereIn('id', $request->ids)->delete();
        return response()->json(['message' => "{$count} rule(s) deleted."]);
    }

    public function toggleActive(PricingRule $pricingRule)
    {
        $pricingRule->update([
            'is_active'  => ! $pricingRule->is_active,
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message'   => 'Status updated.',
            'is_active' => $pricingRule->is_active,
        ]);
    }

    private function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:150'],
            'apply_on'         => ['required', Rule::in(['all', 'product', 'category', 'customer', 'price_list'])],
            'apply_to_id'      => ['nullable', 'integer'],
            'discount_type'    => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value'   => ['required', 'numeric', 'min:0'],
            'min_order_qty'    => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_from'       => ['nullable', 'date'],
            'valid_to'         => ['nullable', 'date', 'after_or_equal:valid_from'],
            'priority'         => ['integer', 'min:0'],
            'is_active'        => ['boolean'],
        ];
    }
}