<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;
use Modules\CRM\Models\Customer;
use Modules\Inventory\Models\Product\ProductVariant;

use App\Models\AuditLog; // ✅ add

#[Middleware('permission:sales.order.view', only: ['index', 'datatable', 'show', 'select2', 'lines'])]
#[Middleware('permission:sales.order.create', only: ['create'])]
#[Middleware('permission:sales.order.store', only: ['store'])]
#[Middleware('permission:sales.order.edit', only: ['edit'])]
#[Middleware('permission:sales.order.update', only: ['update'])]
#[Middleware('permission:sales.order.confirm', only: ['confirm', 'unconfirm'])] // ✅ unconfirm gated too
#[Middleware('permission:sales.order.cancel', only: ['cancel'])]
#[Middleware('permission:sales.order.delete', only: ['destroy'])] // ✅ optional; change to your permission key
class SalesOrderController extends Controller
{
    public function index()
    {
        return view('sales.orders.index');
    }

    public function create()
    {
        return view('sales.orders.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => ['required','integer'],
            'order_date'  => ['required','date'],
            'currency_code' => ['nullable','string','size:3'],
            'reference'   => ['nullable','string','max:100'],
            'remarks'     => ['nullable','string'],
            'lines'       => ['required','array','min:1'],
            'lines.*.product_variant_id' => ['required','integer'],
            'lines.*.qty_ordered'        => ['required','numeric','min:0.0001'],
            'lines.*.unit_price'         => ['required','numeric','min:0'],
        ]);

        $order = DB::transaction(function () use ($request) {

            $hdr = SalesOrder::create([
                'order_no'      => $this->nextOrderNumber(),
                'customer_id'   => $request->customer_id,
                'order_date'    => $request->order_date,
                'currency_code' => $request->input('currency_code', 'USD'),
                'status'        => 'draft',
                'reference'     => $request->reference,
                'remarks'       => $request->remarks,
            ]);

            $lines = collect($request->input('lines', []))
                            ->filter(fn($l) => !empty($l['product_variant_id']))
                            ->map(fn($l) => [
                                'product_variant_id' => (int)$l['product_variant_id'],
                                'description'        => $l['description'] ?? null,
                                'qty_ordered'        => (float)$l['qty_ordered'],
                                'unit_price'         => (float)$l['unit_price'],
                            ])->values();

            if ($lines->isEmpty()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'lines' => 'At least one valid line is required.',
                ]);
            }

            $hdr->lines()->createMany($lines->all());

            $totals = $this->computeTotals($hdr->fresh('lines'));
            $hdr->update($totals);

            return $hdr->fresh('lines');
        });

        // ✅ AUDIT: create
        $this->audit(
            action: 'create',
            description: 'Created sales order draft',
            subject: $order,
            meta: [
                'order_no'    => $order->order_no,
                'customer_id' => $order->customer_id,
                'status'      => $order->status,
                'lines'       => $order->lines->count(),
                'grand_total' => (float)$order->grand_total,
            ]
        );

        return redirect()
            ->route('admin.sales.orders.show', $order->id)
            ->with('success', 'Sales Order created.');
    }

    public function show(SalesOrder $order)
    {
        $order->load([
          'customer',
          'lines.variant.product',
          'deliveries.driver',
          'deliveries.lines',
        ]);
        return view('sales.orders.show', compact('order'));
    }

    public function edit(SalesOrder $order)
    {
        $order->load(['customer','lines.variant.product']);
        return view('sales.orders.edit', compact('order'));
    }

    public function update(Request $request, SalesOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be edited.');
        }

        $request->validate([
            'customer_id' => ['required','integer'],
            'order_date'  => ['required','date'],
            'currency_code' => ['nullable','string','size:3'],
            'reference'   => ['nullable','string','max:100'],
            'remarks'     => ['nullable','string'],
            'lines'       => ['required','array','min:1'],
            'lines.*.product_variant_id' => ['required','integer'],
            'lines.*.qty_ordered'        => ['required','numeric','min:0.0001'],
            'lines.*.unit_price'         => ['required','numeric','min:0'],
        ]);

        $before = $order->only(['customer_id','order_date','currency_code','reference','remarks','status','subtotal','tax_total','grand_total']);

        DB::transaction(function () use ($request, $order) {

            $order->update([
                'customer_id'   => $request->customer_id,
                'order_date'    => $request->order_date,
                'currency_code' => $request->input('currency_code', 'USD'),
                'reference'     => $request->reference,
                'remarks'       => $request->remarks,
            ]);

            // Replace lines (simple + reliable)
            $order->lines()->delete();

            $lines = collect($request->input('lines', []))
                ->filter(fn($l) => !empty($l['product_variant_id']))
                ->map(fn($l) => [
                    'product_variant_id' => (int)$l['product_variant_id'],
                    'description'        => $l['description'] ?? null,
                    'qty_ordered'        => (float)$l['qty_ordered'],
                    'unit_price'         => (float)$l['unit_price'],
                ])->values();

            $order->lines()->createMany($lines->all());

            $totals = $this->computeTotals($order->fresh('lines'));
            $order->update($totals);
        });

        $order->refresh()->load('lines');

        // ✅ AUDIT: update
        $this->audit(
            action: 'update',
            description: 'Updated sales order draft',
            subject: $order,
            meta: [
                'order_no' => $order->order_no,
                'before'   => $before,
                'after'    => $order->only(['customer_id','order_date','currency_code','reference','remarks','status','subtotal','tax_total','grand_total']),
                'lines'    => $order->lines->count(),
            ]
        );

        return redirect()
            ->route('admin.sales.orders.show', $order->id)
            ->with('success', 'Sales Order updated.');
    }

    public function confirm(SalesOrder $order)
    {
        if ($order->status !== 'draft') {
            return response()->json(['message' => 'Only draft orders can be confirmed.'], 422);
        }

        $order->update([
            'status'      => 'confirmed',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        // ✅ AUDIT: confirm
        $this->audit(
            action: 'confirm',
            description: 'Confirmed sales order',
            subject: $order,
            meta: [
                'order_no' => $order->order_no,
                'status'   => $order->status,
            ]
        );

        return response()->json(['message' => 'Order confirmed.']);
    }

    public function unconfirm(SalesOrder $order)
{
    if ($order->status !== 'confirmed') {
        return response()->json(['message' => 'Only confirmed orders can be unconfirmed.'], 422);
    }

    // 🔒 Block if delivery notes already exist for this order
    $hasDeliveries = DB::table('sales_deliveries')
        ->where('sales_order_id', $order->id)
        ->exists();

    if ($hasDeliveries) {
        return response()->json([
            'message' => 'Cannot unconfirm — delivery notes already exist for this order.'
        ], 422);
    }

    $order->update([
        'status'      => 'draft',
        'approved_by' => null,
        'approved_at' => null,
    ]);

    $this->audit(
        action: 'unconfirm',
        description: 'Reverted sales order to draft',
        subject: $order,
        meta: [
            'order_no' => $order->order_no,
            'status'   => $order->status,
        ]
    );

    return response()->json(['message' => 'Order reverted to draft.']);
}


    public function cancel(SalesOrder $order)
    {
        if (in_array($order->status, ['delivered'], true)) {
            return response()->json(['message' => 'Delivered orders cannot be cancelled.'], 422);
        }

        $beforeStatus = $order->status;

        $order->update(['status' => 'cancelled']);

        // ✅ AUDIT: cancel
        $this->audit(
            action: 'cancel',
            description: 'Cancelled sales order',
            subject: $order,
            meta: [
                'order_no'      => $order->order_no,
                'from_status'   => $beforeStatus,
                'to_status'     => $order->status,
            ]
        );

        return response()->json(['message' => 'Order cancelled.']);
    }

    public function destroy(SalesOrder $order)
    {
        if ($order->status !== 'draft') {
            return back()->with('error', 'Only draft orders can be deleted.');
        }

        $meta = [
            'order_no'    => $order->order_no,
            'customer_id' => $order->customer_id,
            'lines'       => $order->lines()->count(),
            'grand_total' => (float)$order->grand_total,
        ];

        $order->lines()->delete();
        $order->delete();

        // ✅ AUDIT: delete
        $this->audit(
            action: 'delete',
            description: 'Deleted sales order draft',
            subject: null,
            meta: $meta + ['order_id' => $order->id]
        );

        return redirect()
            ->route('admin.sales.orders.index')
            ->with('success', 'Order deleted.');
    }

    /** Datatable (NO audit) */
    public function datatable(Request $request)
    {
        $q = SalesOrder::query()
            ->with('customer:id,name')
            ->select(['id','order_no','customer_id','order_date','currency_code','status','grand_total','created_at'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $q->where('customer_id', $request->customer_id);
        }
        if ($request->filled('order_no')) {
            $q->where('order_no', 'like', '%'.$request->order_no.'%');
        }
        if ($request->filled('date_from')) {
            $q->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $q->whereDate('order_date', '<=', $request->date_to);
        }

        return DataTables::eloquent($q)
            ->addColumn('customer', fn($r) => $r->customer?->name ?? '-')
            ->addColumn('order_date_fmt', fn($r) => optional($r->order_date)->format('d-m-Y'))
            ->addColumn('grand_total_fmt', fn($r) => number_format((float)$r->grand_total, 2))
            ->addColumn('actions', fn($r) => view('sales.orders.partials.actions', compact('r'))->render())
            ->addColumn('created_at', fn($r) => date('d-m-Y', strtotime($r->created_at)) ?? '-')
            ->rawColumns(['actions'])
            ->make(true);
    }

    /** Select2 (NO audit) */
    public function select2(Request $request)
    {
        $q = $request->get('q', '');

        $orders = SalesOrder::query()
            ->when($q, fn($qry) => $qry->where('order_no', 'like', "%{$q}%"))
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id','order_no','status']);

        return $orders->map(fn($o) => [
            'id'   => $o->id,
            'text' => $o->order_no . ' (' . $o->status . ')',
        ]);
    }
    
    /** Select2 — filtered by status */
    public function select2ByStatus(Request $request)
    {
        $q = $request->get('q', '');
        $status = $request->get('status'); 
        // can be single or array: status=confirmed OR status[]=confirmed&status[]=partial
    
        $qry = SalesOrder::query()
            ->select(['id','order_no','status'])
            ->orderByDesc('id');
    
        if ($status) {
            if (is_array($status)) {
                $qry->whereIn('status', $status);
            } else {
                $qry->where('status', $status);
            }
        }
    
        if ($q) {
            $qry->where('order_no', 'like', "%{$q}%");
        }
    
        $orders = $qry->limit(20)->get();
    
        return $orders->map(fn($o) => [
            'id' => $o->id,
            'text' => $o->order_no . ' (' . strtoupper($o->status) . ')',
        ]);
    }


    /**
     * Lines endpoint for Delivery Note create page (NO audit)
     * returns { customer_id, lines: [...] }
     */
    public function lines(SalesOrder $order)
    {
        $order->load([
            'customer:id,name',
            'lines.variant.product'
        ]);
    
        // ✅ Delivered subquery: per sales_order_line_id
        $deliveredMap = DB::table('sales_delivery_lines as sdl')
            ->join('sales_deliveries as sd', 'sd.id', '=', 'sdl.sales_delivery_id')
            ->where('sd.sales_order_id', $order->id)
            ->where('sd.status', 'delivered') // ✅ only completed deliveries
            ->whereNotNull('sdl.sales_order_line_id')
            ->groupBy('sdl.sales_order_line_id')
            ->pluck(DB::raw('SUM(sdl.qty_delivered_actual)'), 'sdl.sales_order_line_id'); // [line_id => sum]
    
        $lines = $order->lines->map(function ($l) use ($deliveredMap) {
    
            $variantLabel =
                ($l->variant?->product?->product_name ? $l->variant->product->product_name.' - ' : '')
                . ($l->variant?->sku ?? ('Variant #'.$l->product_variant_id));
    
            $delivered = (float) ($deliveredMap[$l->id] ?? 0);
    
            return [
                'id'                 => $l->id, // sales_order_line_id
                'product_variant_id' => $l->product_variant_id,
                'variant_label'      => $variantLabel,
                'description'        => $l->description,
                'qty_ordered'        => (float) $l->qty_ordered,
                'qty_delivered'      => $delivered, // ✅ computed
                'unit_price'         => (float) $l->unit_price,
            ];
        });
    
        return response()->json([
            'customer_id'   => $order->customer_id,
            'customer_name' => $order->customer?->name,
            'lines'         => $lines,
        ]);
    }


    /** ---------------- Helpers ---------------- */

    protected function computeTotals(SalesOrder $order): array
    {
        $subtotal = $order->lines->sum(fn($l) => ((float)$l->qty_ordered) * ((float)$l->unit_price));
        $tax = 0;
        $grand = $subtotal + $tax;

        return [
            'subtotal'    => $subtotal,
            'tax_total'   => $tax,
            'grand_total' => $grand,
        ];
    }

    /**
     * Prefix generator (switch-based as you requested).
     */
    protected function nextOrderNumber(): string
    {
        $mode = 'SO_DATE_SEQ';

        switch ($mode) {
            case 'SO_SEQ':
                $next = 1 + (int) SalesOrder::max('id');
                return 'SO-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);

            case 'SO_YEAR_SEQ':
                $year = now()->format('Y');
                $count = SalesOrder::whereYear('created_at', $year)->count() + 1;
                return 'SO-' . $year . '-' . str_pad((string)$count, 6, '0', STR_PAD_LEFT);

            case 'SO_DATE_SEQ':
            default:
                $date = now()->format('Ymd');
                $count = SalesOrder::whereDate('created_at', now()->toDateString())->count() + 1;
                return 'SO-' . $date . '-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
        }
    }

    /**
     * ✅ AUDIT helper (same pattern as your StockIssueController)
     */
    protected function audit(
        string $action,
        ?string $description = null,
        $subject = null,
        array $meta = []
    ): void {
        $user = auth()->user();

        AuditLog::create([
            'user_id'      => $user?->id,
            'module'       => 'sales.orders',
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->id,
            'route'        => request()->route()?->getName(),
            'url'          => request()->fullUrl(),
            'method'       => request()->method(),
            'ip'           => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'meta'         => $meta,
        ]);
    }
}
