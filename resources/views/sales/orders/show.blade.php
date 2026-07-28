@extends('layouts.master')

@section('title', 'Sales Order')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Sales Order</h1>
            <p class="text-muted mb-0">Sales / Orders</p>
        </div>

        <div class="d-flex gap-2">
            @can('sales.order.edit')
                @if(($order->status ?? 'draft') === 'draft')
                    <a href="{{ route('admin.sales.orders.edit', $order->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                @endif
            @endcan

            <a href="{{ route('admin.sales.orders.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    {{-- Summary --}}
    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary">
                <i class="fas fa-info-circle mr-1"></i> Order Details
            </h6>

            <span class="badge
                @if(($order->status ?? '') === 'delivered') badge-success
                @elseif(($order->status ?? '') === 'partial') badge-warning
                @elseif(($order->status ?? '') === 'confirmed') badge-primary
                @elseif(($order->status ?? '') === 'cancelled') badge-danger
                @else badge-secondary
                @endif
            ">
                {{ strtoupper($order->status ?? 'DRAFT') }}
            </span>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Order No</div>
                    <div class="font-weight-bold">{{ $order->order_no }}</div>
                </div>

                <div class="col-md-5">
                    <div class="text-muted small">Customer</div>
                    <div class="font-weight-bold">{{ $order->customer->name ?? ('Customer #'.$order->customer_id) }}</div>
                </div>

                <div class="col-md-2">
                    <div class="text-muted small">Order Date</div>
                    <div class="font-weight-bold">{{ \Carbon\Carbon::parse($order->order_date)->format('d M Y') }}</div>
                </div>

                <div class="col-md-2">
                    <div class="text-muted small">Currency</div>
                    <div class="font-weight-bold">{{ $order->currency_code }}</div>
                </div>

                <div class="col-md-12">
                    <div class="text-muted small">Reference</div>
                    <div class="font-weight-bold">{{ $order->reference ?: '-' }}</div>
                </div>

                <div class="col-md-12">
                    <div class="text-muted small">Remarks</div>
                    <div class="font-weight-bold">{{ $order->remarks ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Delivery Notes (if any) --}}
    @php
        // Expecting controller to eager-load: $order->deliveries lines driver
        $deliveries = $order->deliveries ?? collect();

        // delivered qty per SO line (controller should pass this map ideally)
        // fallback: build a map from loaded delivery lines (only delivered deliveries)
        $deliveredMap = [];
        foreach ($deliveries as $d) {
            if (($d->status ?? '') !== 'delivered') continue;
            foreach (($d->lines ?? []) as $dl) {
                $solId = $dl->sales_order_line_id ?? null;
                if (!$solId) continue;
                $deliveredMap[$solId] = ($deliveredMap[$solId] ?? 0) + (float)($dl->qty_delivered_actual ?? 0);
            }
        }
    @endphp

    @if($deliveries->count() > 0)
        <div class="card shadow mb-3">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-primary">
                    <i class="fas fa-truck mr-1"></i> Delivery Notes
                </h6>
                <span class="text-muted small">{{ $deliveries->count() }} record(s)</span>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>Delivery No</th>
                                <th>Status</th>
                                <th>Ship Date</th>
                                <th>Driver</th>
                                <th class="text-right">Lines</th>
                                <th class="text-right">Qty Delivered</th>
                                <th>Remarks</th>
                                <th style="width:140px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($deliveries as $i => $d)
                                @php
                                    $qtyDelivered = 0;
                                    foreach(($d->lines ?? []) as $dl){
                                        $qtyDelivered += (float)($dl->qty_delivered_actual ?? 0);
                                    }

                                    $dBadge = match($d->status ?? 'draft') {
                                        'delivered'  => 'badge-success',
                                        'in_transit' => 'badge-info',
                                        'assigned'   => 'badge-primary',
                                        'draft'      => 'badge-secondary',
                                        'cancelled'  => 'badge-danger',
                                        default      => 'badge-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $i + 1 }}</td>
                                    <td class="font-weight-bold">{{ $d->delivery_no ?? ('DN #'.$d->id) }}</td>
                                    <td>
                                        <span class="badge {{ $dBadge }}">{{ strtoupper($d->status ?? 'DRAFT') }}</span>
                                    </td>
                                    <td>{{ $d->ship_date ? \Carbon\Carbon::parse($d->ship_date)->format('d M Y') : '-' }}</td>
                                    <td>{{ $d->driver->name ?? ($d->driver_name ?? '-') }}</td>
                                    <td class="text-right">{{ is_countable($d->lines ?? null) ? count($d->lines) : 0 }}</td>
                                    <td class="text-right">{{ number_format($qtyDelivered, 4) }}</td>
                                    <td>{{ $d->remarks ?? '-' }}</td>
                                    <td>
                                        @can('sales.delivery.view')
                                            <a href="{{ route('admin.sales.deliveries.show', $d->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye mr-1"></i> View
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mb-0">
                    <strong>Note:</strong> “Qty Delivered” above reflects the sum of <code>qty_delivered_actual</code> on each delivery note.
                    Only <strong>DELIVERED</strong> notes are counted toward “Qty Delivered” in the order lines below.
                </div>
            </div>
        </div>
    @endif

    {{-- Lines --}}
    <div class="card shadow">
        <div class="card-header bg-white">
            <h6 class="mb-0 text-primary">
                <i class="fas fa-list mr-1"></i> Order Lines
            </h6>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Variant</th>
                            <th class="text-right">Qty Ordered</th>
                            <th class="text-right">Qty Delivered</th>
                            <th class="text-right">Remaining</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($order->lines ?? []) as $i => $ln)
                            @php
                                $delivered = (float)($deliveredMap[$ln->id] ?? 0);
                                $ordered   = (float)($ln->qty_ordered ?? 0);
                                $remaining = max(0, $ordered - $delivered);
                                $lineTotal = (float)($ln->line_total ?? ($ordered * (float)($ln->unit_price ?? 0)));
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>
                                    <div class="font-weight-bold">
                                        {{ $ln->variant->product->product_name ?? 'Item' }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $ln->variant->sku ?? ('Variant #'.$ln->product_variant_id) }}
                                    </div>
                                    @if(!empty($ln->description))
                                        <div class="text-muted small">{{ $ln->description }}</div>
                                    @endif
                                </td>
                                <td class="text-right">{{ number_format($ordered, 4) }}</td>
                                <td class="text-right">{{ number_format($delivered, 4) }}</td>
                                <td class="text-right">{{ number_format($remaining, 4) }}</td>
                                <td class="text-right">{{ number_format((float)$ln->unit_price, 4) }}</td>
                                <td class="text-right">{{ number_format($lineTotal, 4) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No lines found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <div style="min-width: 320px;">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Subtotal</span>
                        <strong>{{ number_format((float)($order->subtotal ?? 0), 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Tax</span>
                        <strong>{{ number_format((float)($order->tax_total ?? 0), 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2 mt-2">
                        <span class="text-muted">Grand Total</span>
                        <strong>{{ number_format((float)($order->grand_total ?? 0), 2) }}</strong>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
