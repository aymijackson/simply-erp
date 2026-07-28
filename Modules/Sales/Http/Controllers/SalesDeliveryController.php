<?php

namespace Modules\Sales\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesDeliveryLine;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderLine;

use Modules\CRM\Models\Customer;
use Modules\Inventory\Models\Product\ProductVariant;
use Barryvdh\DomPDF\Facade\Pdf;

class SalesDeliveryController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:sales.deliveries.view')->only(['index','datatable','show','select2ConfirmedOrders','orderPayload','storeAvailable']);
        $this->middleware('can:sales.deliveries.create')->only(['create','store']);
        $this->middleware('can:sales.deliveries.update')->only(['edit','update']);
        $this->middleware('can:sales.deliveries.post')->only(['post']);
        $this->middleware('can:sales.deliveries.cancel')->only(['cancel']);
        $this->middleware('can:sales.deliveries.delete')->only(['destroy']);
    }
    
    // If you have a base controller/trait providing audit(), keep it.
    // Otherwise add your own audit logger here.
    private function audit(string $action, string $description, $subject = null, array $meta = []): void
    {
        // Example: adjust to your audit system
        // audit()->log($action, $description, $subject, $meta);
    }

    public function index()
    {
        return view('sales.deliveries.index');
    }

    public function datatable(Request $request)
    {
        // Minimal server-side datatable (you can replace with Yajra if you’re using it)
        $q = SalesDelivery::query()
            ->with(['customer','order','driver','vehicle'])
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }
        if ($request->filled('customer_id')) {
            $q->where('customer_id', (int)$request->customer_id);
        }

        $rows = $q->paginate((int)($request->length ?? 10));

        $data = $rows->map(function ($d) {
            return [
                'id'         => $d->id,
                'delivery_no'=> $d->delivery_no,
                'order'      => $d->order?->order_no ?? '-',
                'customer'   => $d->customer?->name ?? ('Customer #'.$d->customer_id),
                'driver'     => $d->driver?->full_name ?? '-', // driver accessor
                'vehicle'    => $d->vehicle?->registration_no ?? '-',
                'status'     => '<span class="badge badge-'.$d->status_badge.'">'.strtoupper($d->status).'</span>',
                'ship_date'  => $d->ship_date?->format('d M Y') ?? '-',
                'created'    => optional($d->created_at)->format('d M Y, H:i'),
                'actions'    => view('sales.deliveries.partials.actions', compact('d'))->render(),
            ];
        })->values();

        return response()->json([
            'draw'            => (int)($request->draw ?? 1),
            'recordsTotal'    => $rows->total(),
            'recordsFiltered' => $rows->total(),
            'data'            => $data,
        ]);
    }

    public function create()
    {
        return view('sales.deliveries.form', [
            'delivery' => new SalesDelivery(),
            'mode'     => 'create',
        ]);
    }

    public function edit(SalesDelivery $delivery)
    {
        if (in_array($delivery->status, ['posted','cancelled'], true)) {
            $this->audit(
                action: 'edit_blocked',
                description: 'Attempted to edit non-draft delivery',
                subject: $delivery,
                meta: ['status' => $delivery->status]
            );
            return back()->with('error', 'You cannot edit a posted/cancelled delivery.');
        }

        $delivery->load(['lines.variant.product', 'lines.orderLine', 'customer', 'order', 'driver', 'vehicle']);

        return view('sales.deliveries.form', [
            'delivery' => $delivery,
            'mode'     => 'edit',
        ]);
    }

    public function store(Request $request)
    {
        $payload = $this->validateDelivery($request);

        return DB::transaction(function () use ($payload, $request) {

            $delivery = SalesDelivery::create([
                'delivery_no'      => $payload['delivery_no'] ?? null,
                'sales_order_id'   => $payload['sales_order_id'],
                'customer_id'      => $payload['customer_id'],
                'driver_id'        => $payload['driver_id'] ?? null,
                'vehicle_id'       => $payload['vehicle_id'] ?? null,
                'location_store_id'=> $payload['location_store_id'] ?? null,
                'ship_date'        => $payload['ship_date'] ?? now()->toDateString(),
                'remarks'          => $payload['remarks'] ?? null,
                'status'           => 'draft',
            ]);

            $this->syncLines($delivery, $payload['lines'] ?? []);

            $this->audit(
                action: 'create',
                description: 'Created sales delivery (draft)',
                subject: $delivery,
                meta: ['delivery_no' => $delivery->delivery_no, 'order_id' => $delivery->sales_order_id]
            );

            return response()->json([
                'message' => 'Delivery created.',
                'id'      => $delivery->id,
                'redirect'=> route('admin.sales.deliveries.edit', $delivery->id),
            ]);
        });
    }

    public function update(Request $request, SalesDelivery $delivery)
    {
        if (in_array($delivery->status, ['posted','cancelled'], true)) {
            $this->audit(
                action: 'update_blocked',
                description: 'Attempted to update non-draft delivery',
                subject: $delivery,
                meta: ['status' => $delivery->status]
            );
            return response()->json(['message' => 'You cannot update a posted/cancelled delivery.'], 422);
        }
    
        $payload = $this->validateDelivery($request, $delivery->id);
    
        return DB::transaction(function () use ($payload, $delivery) {
    
            $delivery->update([
                'delivery_no'       => $payload['delivery_no'] ?? $delivery->delivery_no,
                'sales_order_id'    => $payload['sales_order_id'],
                'customer_id'       => $payload['customer_id'],
                'driver_id'         => $payload['driver_id'] ?? null,
                'vehicle_id'        => $payload['vehicle_id'] ?? null,
                'location_store_id' => $payload['location_store_id'] ?? null,
                'ship_date'         => $payload['ship_date'] ?? $delivery->ship_date,
                'remarks'           => $payload['remarks'] ?? null,
            ]);
    
            // Rebuild lines safely
            $delivery->lines()->delete();
    
            // IMPORTANT: compute caps server-side (no qty_remaining from client)
            $this->syncLines($delivery, $payload['lines'] ?? [], $delivery->id);
    
            $this->audit(
                action: 'update',
                description: 'Updated sales delivery (draft)',
                subject: $delivery,
                meta: ['delivery_id' => $delivery->id]
            );
    
            return response()->json(['message' => 'Delivery updated.']);
        });
    }

    public function show(SalesDelivery $delivery)
    {
        $delivery->load(['lines.variant.product','lines.store','customer','order','driver','vehicle']);
        return view('sales.deliveries.show', compact('delivery'));
    }

    public function post(SalesDelivery $delivery)
    {
        if ($delivery->status !== 'draft') {
            return response()->json(['message' => 'Only draft deliveries can be posted.'], 422);
        }

        $delivery->load(['lines']);

        if ($delivery->lines->isEmpty()) {
            return response()->json(['message' => 'Add at least one line before posting.'], 422);
        }

        // You can add stock deduction + sales_order delivery updates here (transaction).
        // For now: mark as posted + set delivered_at if all qty_delivered_actual present.
        $delivery->update([
            'status'      => 'posted',
            'delivered_at'=> now(),
        ]);

        $this->audit('post', 'Posted sales delivery', $delivery, ['delivery_id' => $delivery->id]);

        return response()->json(['message' => 'Delivery posted.']);
    }

    public function cancel(SalesDelivery $delivery)
    {
        if ($delivery->status !== 'draft') {
            return response()->json(['message' => 'Only draft deliveries can be cancelled.'], 422);
        }

        $delivery->update(['status' => 'cancelled']);

        $this->audit('cancel', 'Cancelled sales delivery', $delivery, ['delivery_id' => $delivery->id]);

        return response()->json(['message' => 'Delivery cancelled.']);
    }

    public function destroy(SalesDelivery $delivery)
    {
        if ($delivery->status !== 'draft') {
            return response()->json(['message' => 'Only draft deliveries can be deleted.'], 422);
        }

        $id = $delivery->id;
        $delivery->delete();

        $this->audit('delete', 'Deleted sales delivery', null, ['delivery_id' => $id]);

        return response()->json(['message' => 'Deleted.']);
    }

    /**
     * ✅ Select2: confirmed sales orders only
     */
    public function select2ConfirmedOrders(Request $request)
    {
        $q = trim((string)$request->q);

        $orders = SalesOrder::query()
            ->where('status', 'confirmed')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('order_no', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id','order_no','customer_id','order_date']);

        $results = $orders->map(fn($o) => [
            'id'   => $o->id,
            'text' => $o->order_no . ' ('.$o->order_date.')',
        ]);

        return response()->json(['results' => $results]);
    }

    /**
     * ✅ When order selected -> auto-populate customer + lines + remaining qty
     */
    public function orderPayload(SalesOrder $order)
    {
        if ($order->status !== 'confirmed') {
            return response()->json(['message' => 'Only confirmed orders can be delivered.'], 422);
        }

        $order->load(['customer', 'lines.variant.product']);

        // Remaining logic: if you track delivered qty elsewhere, replace this.
        // For now remaining = qty_ordered (or qty_ordered - qty_delivered if you store it)
        $lines = $order->lines->map(function ($ln) {
            $ordered = (float)$ln->qty_ordered;
            $delivered = (float)($ln->qty_delivered ?? 0);
            $remaining = max(0, $ordered - $delivered);

            return [
                'sales_order_line_id' => $ln->id,
                'product_variant_id'  => $ln->product_variant_id,
                'variant_text'        => ($ln->variant?->product?->product_name ?? 'Item').' - '.($ln->variant?->sku ?? ('Variant #'.$ln->product_variant_id)),
                'qty_ordered'         => $ordered,
                'qty_delivered'       => $delivered,
                'qty_remaining'       => $remaining,
                'unit_price'          => (float)$ln->unit_price,
            ];
        })->values();

        return response()->json([
            'order' => [
                'id'        => $order->id,
                'order_no'  => $order->order_no,
                'customer_id' => $order->customer_id,
                'customer_text' => $order->customer?->name ?? ('Customer #'.$order->customer_id),
            ],
            'lines' => $lines,
        ]);
    }

    /**
     * ✅ Store availability endpoint (used to cap qty when store selected)
     * Replace v_stock_levels + qty_on_hand with your real stock view/table.
     */
    public function storeAvailable(Request $request)
    {
        $request->validate([
            'location_store_id'  => ['required','integer'],
            'product_variant_id' => ['required','integer'],
        ]);

        $storeId   = (int)$request->location_store_id;
        $variantId = (int)$request->product_variant_id;

        $available = (float)(DB::table('v_stock_levels')
            ->where('location_store_id', $storeId)
            ->where('product_variant_id', $variantId)
            ->value('qty_on_hand') ?? 0);

        return response()->json(['available' => max(0, $available)]);
    }

    private function validateDelivery(Request $request, ?int $deliveryId = null): array
    {
        return $request->validate([
            'delivery_no'       => ['nullable','string','max:30'],
            'sales_order_id'    => ['required','integer','exists:sales_orders,id'],
            'customer_id'       => ['required','integer','exists:customers,id'],
            'driver_id'         => ['nullable','integer','exists:drivers,id'],
            'vehicle_id'        => ['nullable','integer','exists:vehicles,id'],
            'location_store_id' => ['nullable','integer','exists:location_stores,id'],
            'ship_date'         => ['nullable','date'],
            'remarks'           => ['nullable','string'],

            'lines'                         => ['required','array','min:1'],
            'lines.*.sales_order_line_id'   => ['nullable','integer','exists:sales_order_lines,id'],
            'lines.*.product_variant_id'    => ['required','integer','exists:product_variants,id'],
            'lines.*.location_store_id'     => ['nullable','integer','exists:location_stores,id'],
            'lines.*.qty_to_deliver'        => ['required','numeric','min:0'],
            'lines.*.qty_delivered_actual'  => ['nullable','numeric','min:0'],
            'lines.*.unit_cost'             => ['nullable','numeric','min:0'],
            'lines.*.qty_remaining'         => ['nullable','numeric','min:0'], // sent by UI for server-side capping
        ]);
    }

    private function syncLines(SalesDelivery $delivery, array $lines, ?int $excludeDeliveryId = null): void
    {
        foreach ($lines as $ln) {
    
            $orderLineId = !empty($ln['sales_order_line_id']) ? (int)$ln['sales_order_line_id'] : null;
            $variantId   = (int)$ln['product_variant_id'];
            $storeId     = !empty($ln['location_store_id']) ? (int)$ln['location_store_id'] : null;
    
            $requestedQty = (float)($ln['qty_to_deliver'] ?? 0);
            $requestedQty = max(0, $requestedQty);
    
            // 1) Cap by remaining on the Sales Order line (server computed)
            $maxByOrder = 0.0;
    
            if ($orderLineId) {
                $orderLine = SalesOrderLine::query()
                    ->where('id', $orderLineId)
                    ->where('sales_order_id', $delivery->sales_order_id)
                    ->first();
    
                if ($orderLine) {
                    $ordered = (float)$orderLine->qty_ordered;
    
                    // Sum of already posted deliveries for this order line
                    $postedDelivered = (float) DB::table('sales_delivery_lines as sdl')
                        ->join('sales_deliveries as sd', 'sd.id', '=', 'sdl.sales_delivery_id')
                        ->where('sd.sales_order_id', $delivery->sales_order_id)
                        ->where('sd.status', 'posted')
                        ->where('sdl.sales_order_line_id', $orderLineId)
                        ->when($excludeDeliveryId, fn($q) => $q->where('sd.id', '!=', $excludeDeliveryId))
                        ->sum('sdl.qty_to_deliver');
    
                    $maxByOrder = max(0, $ordered - $postedDelivered);
                }
            } else {
                /**
                 * If you ever allow lines without sales_order_line_id:
                 * you can cap by the matching variant line in the order.
                 */
                $orderLine = SalesOrderLine::query()
                    ->where('sales_order_id', $delivery->sales_order_id)
                    ->where('product_variant_id', $variantId)
                    ->first();
    
                if ($orderLine) {
                    $ordered = (float)$orderLine->qty_ordered;
    
                    $postedDelivered = (float) DB::table('sales_delivery_lines as sdl')
                        ->join('sales_deliveries as sd', 'sd.id', '=', 'sdl.sales_delivery_id')
                        ->where('sd.sales_order_id', $delivery->sales_order_id)
                        ->where('sd.status', 'posted')
                        ->where('sdl.product_variant_id', $variantId)
                        ->when($excludeDeliveryId, fn($q) => $q->where('sd.id', '!=', $excludeDeliveryId))
                        ->sum('sdl.qty_to_deliver');
    
                    $maxByOrder = max(0, $ordered - $postedDelivered);
                }
            }
    
            $qty = min($requestedQty, $maxByOrder);
    
            // 2) Cap by store availability if store selected
            if ($storeId) {
                $available = (float) (DB::table('v_stock_levels')
                    ->where('location_store_id', $storeId)
                    ->where('product_variant_id', $variantId)
                    ->value('qty_on_hand') ?? 0);
    
                $qty = min($qty, max(0, $available));
            }
    
            SalesDeliveryLine::create([
                'sales_delivery_id'     => $delivery->id,
                'sales_order_line_id'   => $orderLineId,
                'location_store_id'     => $storeId,
                'product_variant_id'    => $variantId,
                'qty_to_deliver'        => max(0, $qty),
                'qty_delivered_actual'  => (float)($ln['qty_delivered_actual'] ?? 0),
                'unit_cost'             => $ln['unit_cost'] ?? 0,
            ]);
        }
    }
    
    public function printPdf(SalesDelivery $delivery)
    {
        $delivery->load(['customer','order','driver','vehicle','lines.variant.product','lines.store']);
    
        $pdf = Pdf::loadView('sales.deliveries.print_pdf', compact('delivery'))
            ->setPaper('a4');
    
        return $pdf->stream("DELIVERY-{$delivery->delivery_no}.pdf");
    }

}
