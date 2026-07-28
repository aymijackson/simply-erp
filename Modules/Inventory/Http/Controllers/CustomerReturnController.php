<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

use App\Models\LocationStore;
use Modules\CRM\Models\Customer;
use Modules\Sales\Models\SalesDelivery; // if you have it
use Modules\Inventory\Models\StockEntry;
use Modules\Inventory\Models\StockReturn;
use Modules\Inventory\Services\CustomerReturnService;

class CustomerReturnController extends BaseController
{
    public function __construct()
    {
        $this->middleware('permission:inventory.customer_returns.view')->only(['index','datatable','showJson']);
        $this->middleware('permission:inventory.customer_returns.create')->only(['store']);
        $this->middleware('permission:inventory.customer_returns.edit')->only(['update']);
        $this->middleware('permission:inventory.customer_returns.delete')->only(['destroy']);
        $this->middleware('permission:inventory.customer_returns.approve')->only(['approve']);
        $this->middleware('permission:inventory.customer_returns.post')->only(['post']);
    }

    public function index()
    {
        $data['stores']       = LocationStore::query()->orderBy('name')->get();
        $data['request_uuid'] = (string) Str::uuid(); // optional (NOT idempotent)

        $this->audit(
            'inventory.customer_returns',
            'viewed',
            'Viewed customer returns list',
            null,
            []
        );

        return view('inventory.returns.customer.index', $data);
    }

    public function datatable()
    {
        $q = StockReturn::query()
            ->customer()
            ->with([
                'store:id,name',
                'customer:id,name',
                'postedBy:id,name',
                'origin', // StockEntry
            ])
            ->orderByDesc('stock_returns.created_at');

        return DataTables::eloquent($q)
            ->addColumn('location_store', fn($r) => e($r->store?->name ?? '—'))
            ->addColumn('customer_name', fn($r) => e($r->customer?->name ?? '—'))
            ->addColumn('origin_ref', function ($r) {
                // For customer return origin is StockEntry, show reference or #id
                $ref = $r->origin?->reference ?: ('#'.$r->reference_id);
                return e($ref);
            })
            ->addColumn('status_badge', function ($r) {
                $status = $r->origin?->status ?? $r->status ?? 'draft';
                $map = ['posted'=>'success','approved'=>'primary','draft'=>'secondary','void'=>'danger'];
                $cls = $map[$status] ?? 'secondary';
                return '<span class="badge bg-'.$cls.' text-white">'.e(ucfirst($status)).'</span>';
            })
            ->addColumn('posted_info', function ($r) {
                if (!$r->posted_at) return 'Not Posted/ Info. not available';
                $who = $r->postedBy?->name ? ' by '.e($r->postedBy->name) : '';
                return date('d-m-Y h:i a', strtotime($r->posted_at)).$who;
            })
            ->addColumn('actions', fn($r) => view('inventory.returns.customer.partials.actions', compact('r')))
            ->rawColumns(['status_badge','actions'])
            ->make(true);
    }

    public function store(Request $r, CustomerReturnService $svc)
    {
        $hdr = $r->validate([
            'store_id'      => 'required|exists:location_stores,id',
            'customer_id'   => 'required|exists:customers,id',
            'remarks'       => 'nullable|string|max:255', // stock_entries has remarks (NOT reason)
            'reference'     => 'nullable|string|max:255',
            'entry_date'    => 'required|date',
            'sales_delivery_id' => 'nullable|integer', // optional (we store in reference unless you have a FK)
            'request_uuid'  => 'nullable|uuid', // optional now
        ]) + ['entry_type' => 'cust_return'];

        $lines = collect($r->input('lines', []))
            ->filter(fn($l) => (int)($l['product_variant_id'] ?? 0) > 0 && (float)($l['qty'] ?? 0) > 0)
            ->values()
            ->toArray();

        $entry = $svc->create($hdr, $lines);

        $this->audit(
            'inventory.customer_returns',
            'created',
            'Created customer return draft',
            $entry,
            [
                'store_id'     => $entry->store_id,
                'customer_id'  => $entry->customer_id,
                'reference'    => $entry->reference,
                'entry_date'   => $entry->entry_date,
                'remarks'      => $entry->remarks,
                'lines_count'  => count($lines),
            ]
        );

        return response()->json(['ok' => true, 'message' => 'Saved']);
    }

    public function update(Request $r, StockReturn $return, CustomerReturnService $svc)
    {
        abort_if($return->return_type !== 'customer', 400, 'Invalid return type');

        $hdr = $r->validate([
            'store_id'      => 'required|exists:location_stores,id',
            'customer_id'   => 'required|exists:customers,id',
            'remarks'       => 'nullable|string|max:255',
            'reference'     => 'nullable|string|max:255',
            'entry_date'    => 'required|date',
            'sales_delivery_id' => 'nullable|integer',
        ]);

        $lines = collect($r->input('lines', []))
            ->filter(fn($l) => (int)($l['product_variant_id'] ?? 0) > 0 && (float)($l['qty'] ?? 0) > 0)
            ->values()
            ->toArray();

        $entry = $svc->update($return, $hdr, $lines);

        $this->audit(
            'inventory.customer_returns',
            'updated',
            'Updated customer return draft',
            $entry,
            [
                'stock_return_id' => $return->id,
                'reference_id'    => $return->reference_id,
                'store_id'        => $entry->store_id,
                'customer_id'     => $entry->customer_id,
                'reference'       => $entry->reference,
                'entry_date'      => $entry->entry_date,
                'remarks'         => $entry->remarks,
                'lines_count'     => count($lines),
            ]
        );

        return response()->json(['ok' => true, 'message' => 'Updated']);
    }

    public function approve(StockReturn $return, CustomerReturnService $svc)
    {
        abort_if($return->return_type !== 'customer', 400, 'Invalid return type');

        $entry = $svc->approve($return);

        $this->audit(
            'inventory.customer_returns',
            'approved',
            'Approved customer return',
            $entry,
            [
                'stock_return_id' => $return->id,
                'entry_id'        => $entry->id,
            ]
        );

        return response()->json(['ok' => true, 'message' => 'Approved']);
    }

    public function post(int $id, CustomerReturnService $svc)
{
    $ret = StockReturn::with(['origin', 'origin.lines'])->findOrFail($id);

    if ($ret->return_type !== 'customer') {
        abort(422, 'Not a customer return.');
    }

    /** @var StockEntry|null $entry */
    $entry = ($ret->reference_type === StockEntry::class) ? $ret->origin : null;

    if (!$entry) {
        abort(422, 'Customer return is not linked to a stock entry.');
    }

    if ($entry->status !== 'approved') {
        abort(422, 'Only approved entries can be posted.');
    }

    $before = [
        'entry_status'  => $entry->status,
        'return_status' => $ret->status,
    ];

    DB::transaction(function () use ($svc, $entry, $ret) {

        // Post stock movement (and usually sets entry status)
        $svc->post($entry->fresh('lines'));

        // Safety: ensure entry status is posted
        $entry->refresh();
        if ($entry->status !== 'posted') {
            $entry->update([
                'status'    => 'posted',
                'posted_by' => auth()->id(),
            ]);
        }

        // Mirror status on return header
        $ret->update([
            'status'    => 'posted',
            'posted_at' => now(),
            'posted_by' => auth()->id(),
        ]);
    });

    $after = [
        'entry_status'  => $entry->fresh()->status,
        'return_status' => $ret->fresh()->status,
        'posted_by'     => auth()->id(),
    ];

    $this->audit(
        module: 'inventory.customer_returns',
        action: 'posted',
        description: 'Posted customer return #' . $ret->id,
        subject: $ret,
        meta: [
            'return_id' => $ret->id,
            'entry_id'  => $entry->id,
            'before'    => $before,
            'after'     => $after,
        ]
    );

    return response()->json(['message' => 'Customer return posted']);
}
    
    public function destroy(StockReturn $return, CustomerReturnService $svc)
    {
        abort_if($return->return_type !== 'customer', 400, 'Invalid return type');

        $meta = $svc->delete($return);

        $this->audit(
            'inventory.customer_returns',
            'deleted',
            'Deleted customer return draft',
            null,
            $meta
        );

        return response()->json(['ok' => true, 'message' => 'Deleted']);
    }

    /** Used by edit button to load the full JSON for modal (with Select2-friendly text) */
    public function showJson(StockReturn $return)
    {
        abort_if($return->return_type !== 'customer', 400, 'Invalid return type');

        $return->load([
            'store:id,name',
            'customer:id,name',
            'origin.lines.variant:id,sku',
        ]);

        $entry = $return->origin;

        return response()->json([
            'id' => $return->id,
            'stock_return_id' => $return->id,

            'store' => $return->store ? ['id'=>$return->store->id, 'text'=>$return->store->name] : null,
            'customer' => $return->customer ? ['id'=>$return->customer->id, 'text'=>$return->customer->name] : null,

            'entry_date' => $entry?->entry_date,
            'reference'  => $entry?->reference,
            'remarks'    => $entry?->remarks,

            'lines' => $entry?->lines?->map(fn($l) => [
                'product_variant_id' => $l->product_variant_id,
                'text' => $l->variant?->sku ?? ('Variant #'.$l->product_variant_id),
                'qty' => $l->qty,
                'unit_cost' => $l->unit_cost,
            ])->values() ?? [],
        ]);
    }

    /* Optional select2 for customers */
    public function select2Customers(Request $r)
    {
        $term = $r->q;

        $data = Customer::query()
            ->when($term, fn($q)=>$q->where('name','like',"%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name as text']);

        return response()->json($data);
    }

    /* Optional select2 for sales deliveries (if you have this model) */
    public function select2SalesDeliveries(Request $r)
    {
        $term = $r->q;

        $q = SalesDelivery::query()
            ->when($term, function ($q) use ($term) {
                $q->where('delivery_no','like',"%{$term}%");
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id','delivery_no']);

        return response()->json(
            $q->map(fn($d)=>['id'=>$d->id,'text'=>$d->delivery_no])->values()
        );
    }
    
    public function json(int $id): JsonResponse
    {
        // StockReturn record (customer only)
        $ret = StockReturn::query()
            ->where('return_type', 'customer')
            ->with([
                'store:id,name',
                'customer:id,name',
                'origin', // StockEntry via morphTo
                'lines.variant' // StockReturnLine -> variant
            ])
            ->findOrFail($id);
    
        /** @var \Modules\Inventory\Models\StockEntry|null $entry */
        $entry = ($ret->reference_type === StockEntry::class)
            ? $ret->origin
            : null;
    
        return response()->json([
            'id' => $ret->id,
    
            // header (prefer StockEntry values if linked)
            'store_id'   => $entry?->store_id   ?? $ret->store_id,
            'entry_date' => $entry?->entry_date ?? now()->toDateString(),
            'reference'  => $entry?->reference  ?? null,
            'remarks'    => $entry?->remarks    ?? ($ret->reason ?? null),
    
            // select2-friendly (used by setSelect2Value)
            'store' => [
                'id'   => $entry?->store_id ?? $ret->store_id,
                'text' => $entry?->store?->name ?? $ret->store?->name ?? ('Store #'.($entry?->store_id ?? $ret->store_id)),
            ],
            'customer' => [
                'id'   => $entry?->customer_id ?? $ret->customer_id,
                'text' => $entry?->customer?->name ?? $ret->customer?->name ?? ('Customer #'.($entry?->customer_id ?? $ret->customer_id)),
            ],
    
            // optional sales delivery (if you later add this relation/field)
            // If you store it on stock_entries.sales_delivery_line_id or similar, map it here.
            // For now return null safely:
            'sales_delivery_id'   => null,
            'sales_delivery'      => null,
    
            // lines for modal
            'lines' => $ret->lines->map(function ($l) {
                return [
                    'product_variant_id' => $l->product_variant_id,
                    'sku'                => $l->variant?->sku,
                    'text'               => $l->variant?->sku, // select2 display
                    'qty'                => (float) $l->qty,
                    'unit_cost'          => $l->unit_cost !== null ? (float) $l->unit_cost : null,
                ];
            })->values(),
        ]);
    }
}
