<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Sales\Models\PriceList;
use Modules\Sales\Models\PriceListItem;
use Modules\Sales\Models\PricingRule;
use Modules\Sales\Services\PricingService;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\CRM\Models\Customer;
use Yajra\DataTables\Facades\DataTables;

/**
 * PriceListController
 *
 * ── PERMISSION MAP ───────────────────────────────────────────────────────────
 * Method(s)                              Permission (guard: web)
 * ────────────────────────────────────── ────────────────────────────────────
 * index, datatable, show                 sales.price_lists.view
 * store                                  sales.price_lists.create
 * update                                 sales.price_lists.edit
 * destroy, bulkDelete                    sales.price_lists.delete
 *
 * itemsDatatable, storeItem,
 * updateItem, destroyItem                sales.price_lists.items.manage
 *
 * resolve (AJAX)                         sales.price_lists.view
 * ────────────────────────────────────────────────────────────────────────────
 * @see database/sql/price_list_permissions.sql
 */
class PriceListController extends Controller
{
    public function __construct(protected PricingService $pricing)
    {
        $this->middleware('auth');

        $this->middleware('permission:sales.price_lists.view',
            ['only' => ['index', 'datatable', 'show', 'resolve', 'select2']]);

        $this->middleware('permission:sales.price_lists.create',
            ['only' => ['store']]);

        $this->middleware('permission:sales.price_lists.edit',
            ['only' => ['update']]);

        $this->middleware('permission:sales.price_lists.delete',
            ['only' => ['destroy', 'bulkDelete']]);

        $this->middleware('permission:sales.price_lists.items.manage',
            ['only' => ['itemsDatatable', 'storeItem', 'updateItem', 'destroyItem']]);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function index()
    {
        return view('price_lists.index');
    }

    // ── Datatable ─────────────────────────────────────────────────────────────

    public function datatable(Request $request)
    {
        $q = PriceList::withTrashed(false)
            ->withCount('items')
            ->when($request->type,        fn($q, $v) => $q->where('type', $v))
            ->when($request->currency,    fn($q, $v) => $q->where('currency_code', $v))
            ->when($request->is_active,   fn($q, $v) => $q->where('is_active', (bool)$v))
            ->orderByDesc('is_default')
            ->orderBy('name');

        return DataTables::eloquent($q)
            ->addColumn('status_badge', fn($r) => $r->is_active
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Inactive</span>')
            ->addColumn('default_badge', fn($r) => $r->is_default
                ? '<span class="badge bg-primary">Default</span>' : '')
            ->addColumn('validity', function ($r) {
                $from = $r->valid_from?->format('d M Y') ?? '—';
                $to   = $r->valid_to?->format('d M Y')   ?? '—';
                return "{$from} → {$to}";
            })
            ->addColumn('actions', fn($r) => view('price_lists.partials.actions', compact('r'))->render())
            ->rawColumns(['status_badge', 'default_badge', 'actions'])
            ->make(true);
    }

    // ── Show (detail page with items tab) ─────────────────────────────────────

    public function show(PriceList $priceList)
    {
        $priceList->load(['items.variant.product', 'customers']);
        return view('price_lists.show', compact('priceList'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'code'          => ['nullable', 'string', 'max:50', 'unique:price_lists,code'],
            'currency_code' => ['required', 'string', 'size:3'],
            'type'          => ['required', Rule::in(['sale', 'purchase'])],
            'is_default'    => ['boolean'],
            'is_active'     => ['boolean'],
            'valid_from'    => ['nullable', 'date'],
            'valid_to'      => ['nullable', 'date', 'after_or_equal:valid_from'],
            'notes'         => ['nullable', 'string'],
        ]);

        // Only one default per type+currency
        if (! empty($validated['is_default'])) {
            PriceList::where('type', $validated['type'])
                ->where('currency_code', $validated['currency_code'])
                ->update(['is_default' => false]);
        }

        $priceList = PriceList::create([
            ...$validated,
            'company_id' => auth()->user()->company_id ?? 1,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message'    => 'Price list created.',
            'price_list' => $priceList,
        ], 201);
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function update(Request $request, PriceList $priceList)
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:150'],
            'code'          => ['nullable', 'string', 'max:50',
                                Rule::unique('price_lists', 'code')->ignore($priceList->id)],
            'currency_code' => ['required', 'string', 'size:3'],
            'type'          => ['required', Rule::in(['sale', 'purchase'])],
            'is_default'    => ['boolean'],
            'is_active'     => ['boolean'],
            'valid_from'    => ['nullable', 'date'],
            'valid_to'      => ['nullable', 'date', 'after_or_equal:valid_from'],
            'notes'         => ['nullable', 'string'],
        ]);

        if (! empty($validated['is_default'])) {
            PriceList::where('type', $validated['type'])
                ->where('currency_code', $validated['currency_code'])
                ->where('id', '!=', $priceList->id)
                ->update(['is_default' => false]);
        }

        $priceList->update([...$validated, 'updated_by' => auth()->id()]);

        return response()->json(['message' => 'Price list updated.']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function destroy(PriceList $priceList)
    {
        $priceList->items()->delete();
        $priceList->delete();

        return response()->json(['message' => 'Price list deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => ['required', 'array'], 'ids.*' => 'integer']);

        $count = PriceList::whereIn('id', $request->ids)->count();
        PriceListItem::whereIn('price_list_id', $request->ids)->delete();
        PriceList::whereIn('id', $request->ids)->delete();

        return response()->json(['message' => "{$count} price list(s) deleted."]);
    }

    // ── Items (nested) ────────────────────────────────────────────────────────

    public function itemsDatatable(Request $request, PriceList $priceList)
    {
        $q = PriceListItem::with('variant.product')
            ->where('price_list_id', $priceList->id);

        return DataTables::eloquent($q)
            ->addColumn('variant_name', fn($r) =>
                ($r->variant?->product?->product_name ?? '')
                .' '.($r->variant?->sku ?? ''))
            ->addColumn('actions', fn($r) =>
                '<button class="btn btn-xs btn-warning btn-edit-item"
                    data-id="'.$r->id.'" data-record="'.e(json_encode($r->toArray())).'">
                    <i class="fas fa-pencil-alt"></i></button>
                 <button class="btn btn-xs btn-danger btn-delete-item" data-id="'.$r->id.'">
                    <i class="fas fa-trash"></i></button>')
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function storeItem(Request $request, PriceList $priceList)
    {
        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'unit_price'         => ['required', 'numeric', 'min:0'],
            'min_qty'            => ['nullable', 'numeric', 'min:0.0001'],
        ]);

        $item = $priceList->items()->updateOrCreate(
            [
                'product_variant_id' => $validated['product_variant_id'],
                'min_qty'            => $validated['min_qty'] ?? 1.0,
            ],
            [
                'unit_price' => $validated['unit_price'],
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        return response()->json(['message' => 'Item saved.', 'item' => $item], 201);
    }

    public function updateItem(Request $request, PriceList $priceList, PriceListItem $item)
    {
        abort_unless((int) $item->price_list_id === (int) $priceList->id, 403);

        $validated = $request->validate([
            'unit_price' => ['required', 'numeric', 'min:0'],
            'min_qty'    => ['nullable', 'numeric', 'min:0.0001'],
        ]);

        $item->update([...$validated, 'updated_by' => auth()->id()]);

        return response()->json(['message' => 'Item updated.']);
    }

    public function destroyItem(PriceList $priceList, PriceListItem $item)
    {
        abort_unless((int) $item->price_list_id === (int) $priceList->id, 403);
        $item->delete();

        return response()->json(['message' => 'Item removed.']);
    }

    // ── Select2 ───────────────────────────────────────────────────────────────

    public function select2(Request $request)
    {
        $q = $request->get('q', '');

        return PriceList::active()
            ->when($request->type, fn($qry, $v) => $qry->where('type', $v))
            ->when($request->currency, fn($qry, $v) => $qry->where('currency_code', $v))
            ->when($q, fn($qry) => $qry->where('name', 'like', "%{$q}%"))
            ->limit(20)
            ->get(['id', 'name', 'currency_code', 'type'])
            ->map(fn($r) => [
                'id'   => $r->id,
                'text' => "{$r->name} ({$r->currency_code})",
            ]);
    }

    // ── AJAX: Resolve price for variant+customer+qty ───────────────────────────
    // Called by the sales order create form when a variant is selected.

    public function resolve(Request $request)
    {
        $request->validate([
            'variant_id'    => ['required', 'integer'],
            'qty'           => ['nullable', 'numeric', 'min:0.0001'],
            'customer_id'   => ['nullable', 'integer'],
            'currency_code' => ['nullable', 'string', 'size:3'],
        ]);

        $result = $this->pricing->resolve(
            variantId:    (int) $request->variant_id,
            qty:          (float) ($request->qty ?? 1.0),
            customerId:   $request->customer_id ? (int) $request->customer_id : null,
            currencyCode: $request->input('currency_code', 'USD'),
        );

        return response()->json($result);
    }
}