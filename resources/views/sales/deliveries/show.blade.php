@extends('layouts.master')

@section('title','Delivery Details')
@push('styles')
<style>
@media print {
  .no-print { display:none !important; }
  body { background:#fff !important; }
}
</style>
@endpush
@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Delivery</h1>
            <small class="text-muted">Sales / Deliveries</small>
        </div>
        <div class="d-flex gap-2">
            @if($delivery->status === 'draft')
            <a class="btn btn-primary" href="{{ route('admin.sales.deliveries.edit', $delivery->id) }}">
                <i class="fas fa-edit mr-1"></i> Edit
            </a>
            @elseif($delivery->status == 'posted')
            <button onclick="window.print()" class="btn btn-primary">Print</button>
            <a href="{{ route('admin.sales.deliveries.pdf', $delivery->id) }}"
               target="_blank"
               class="btn btn-sm btn-danger">
                <i class="fas fa-file-pdf"></i> PDF
            </a>
            @endif
            <a class="btn btn-outline-secondary" href="{{ route('admin.sales.deliveries.index') }}">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary"><i class="fas fa-info-circle mr-1"></i> Delivery Header</h6>
            <span class="badge badge-{{ $delivery->status_badge }}">{{ strtoupper($delivery->status) }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Delivery No</div>
                    <div class="fw-bold">{{ $delivery->delivery_no ?? ('#'.$delivery->id) }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Sales Order</div>
                    <div class="fw-bold">{{ $delivery->order?->order_no ?? '-' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">Customer</div>
                    <div class="fw-bold">{{ $delivery->customer?->name ?? ('Customer #'.$delivery->customer_id) }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Driver</div>
                    <div class="fw-bold">{{ $delivery->driver?->full_name ?? '-' }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Vehicle</div>
                    <div class="fw-bold">{{ $delivery->vehicle?->registration_no ?? '-' }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Ship Date</div>
                    <div class="fw-bold">{{ optional($delivery->ship_date)->format('d M Y') ?? '-' }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Delivered At</div>
                    <div class="fw-bold">{{ optional($delivery->delivered_at)->format('d M Y, H:i') ?? '-' }}</div>
                </div>

                <div class="col-md-12">
                    <div class="text-muted small">Remarks</div>
                    <div class="fw-bold">{{ $delivery->remarks ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-white">
            <h6 class="mb-0 text-primary"><i class="fas fa-list mr-1"></i> Delivery Lines</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th>#</th>
                            <th>Variant</th>
                            <th>Store</th>
                            <th class="text-end">Qty To Deliver</th>
                            <th class="text-end">Qty Delivered (Actual)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($delivery->lines as $i => $ln)
                        <tr>
                            <td>{{ $i+1 }}</td>
                            <td>
                                <div class="fw-bold">{{ $ln->variant?->product?->product_name ?? 'Item' }}</div>
                                <div class="text-muted small">{{ $ln->variant?->sku ?? ('Variant #'.$ln->product_variant_id) }}</div>
                            </td>
                            <td>{{ $ln->store?->name ?? '-' }}</td>
                            <td class="text-end">{{ number_format((float)$ln->qty_to_deliver, 0) }}</td>
                            <td class="text-end">{{ number_format((float)$ln->qty_delivered_actual, 0) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">No lines found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
