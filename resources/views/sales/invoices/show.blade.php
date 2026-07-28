{{-- resources/views/sales/invoices/show.blade.php --}}
@extends('layouts.master')

@section('title', 'Invoice')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 text-primary mb-0">Sales Invoice</h1>
            <small class="text-muted">Sales / Invoices</small>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.sales.invoices.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>

            @can('sales.invoice.edit')
                @if(($invoice->status ?? 'draft') === 'draft')
                    <a href="{{ route('admin.sales.invoices.edit', $invoice->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                @endif
            @endcan

            @can('sales.invoice.print')
                <a href="{{ route('admin.sales.invoices.print', $invoice->id) }}" target="_blank" class="btn btn-outline-dark">
                    <i class="fas fa-print mr-1"></i> Print PDF
                </a>
            @endcan
        </div>
    </div>

    {{-- Header --}}
    <div class="card shadow mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0 text-primary"><i class="fas fa-file-invoice mr-1"></i> Invoice Details</h6>
            <span class="badge badge-{{ $invoice->status_badge ?? 'secondary' }}">{{ strtoupper($invoice->status ?? 'draft') }}</span>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Invoice No</div>
                    <div class="fw-bold">{{ $invoice->invoice_no ?? ('INV-'.$invoice->id) }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Invoice Date</div>
                    <div class="fw-bold">{{ optional($invoice->invoice_date)->format('d M Y') ?? '-' }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Due Date</div>
                    <div class="fw-bold">{{ optional($invoice->due_date)->format('d M Y') ?? '-' }}</div>
                </div>

                <div class="col-md-3">
                    <div class="text-muted small">Currency</div>
                    <div class="fw-bold">{{ $invoice->currency_code ?? 'NGN' }}</div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">Customer</div>
                    <div class="fw-bold">{{ $invoice->customer?->name ?? ('Customer #'.$invoice->customer_id) }}</div>
                    @if(!empty($invoice->customer?->email) || !empty($invoice->customer?->phone))
                        <div class="text-muted small">
                            {{ $invoice->customer?->email ? ('Email: '.$invoice->customer->email) : '' }}
                            {{ $invoice->customer?->email && $invoice->customer?->phone ? ' • ' : '' }}
                            {{ $invoice->customer?->phone ? ('Phone: '.$invoice->customer->phone) : '' }}
                        </div>
                    @endif
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">Sales Order</div>
                    <div class="fw-bold">
                        @if($invoice->sales_order_id)
                            <a href="{{ route('admin.sales.orders.show', $invoice->sales_order_id) }}">
                                {{ $invoice->order?->order_no ?? ('Order #'.$invoice->sales_order_id) }}
                            </a>
                        @else
                            -
                        @endif
                    </div>
                </div>

                <div class="col-md-12">
                    <div class="text-muted small">Remarks</div>
                    <div class="fw-bold">{{ $invoice->remarks ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Lines --}}
    <div class="card shadow mb-3">
        <div class="card-header bg-white">
            <h6 class="mb-0 text-primary"><i class="fas fa-list mr-1"></i> Invoice Lines</h6>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Item</th>
                            <th>Description</th>
                            <th class="text-end" style="width:120px;">Qty</th>
                            <th class="text-end" style="width:150px;">Unit Price</th>
                            <th class="text-end" style="width:120px;">Tax %</th>
                            <th class="text-end" style="width:150px;">Tax Amt</th>
                            <th class="text-end" style="width:170px;">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(($invoice->lines ?? []) as $i => $ln)
                            @php
                                $type = $ln->charge_code ?? 'product';
                                $itemText = $ln->variant_text
                                    ?? (($ln->variant?->product?->product_name ?? 'Item') . ' - ' . ($ln->variant?->sku ?? ('Variant #'.$ln->product_variant_id)));
                                if ($type !== 'product') $itemText = strtoupper($type);
                            @endphp
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>
                                    <div class="fw-bold">{{ $itemText }}</div>
                                    @if($type === 'percent')
                                        <div class="text-muted small">
                                            Basis: {{ $ln->calc_basis ?? 'subtotal' }} • Rate: {{ number_format((float)($ln->calc_percent ?? 0), 2) }}%
                                        </div>
                                    @endif
                                    @if($type === 'discount')
                                        <span class="badge badge-danger">DISCOUNT</span>
                                    @elseif($type === 'custom')
                                        <span class="badge badge-secondary">CUSTOM</span>
                                    @elseif($type === 'percent')
                                        <span class="badge badge-info">% CHARGE</span>
                                    @endif
                                </td>
                                <td>{{ $ln->description ?: '-' }}</td>
                                <td class="text-end">{{ number_format((float)($ln->qty ?? 1), 2) }}</td>
                                <td class="text-end">{{ number_format((float)($ln->unit_price ?? 0), 2) }}</td>
                                <td class="text-end">{{ number_format((float)($ln->tax_rate ?? 0), 2) }}</td>
                                <td class="text-end">{{ number_format((float)($ln->tax_amount ?? 0), 2) }}</td>
                                <td class="text-end">{{ number_format((float)($ln->line_total ?? 0), 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No lines found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-end mt-3">
                <div style="min-width: 360px;">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Subtotal</span>
                        <strong>{{ number_format((float)($invoice->subtotal ?? 0), 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Tax Total</span>
                        <strong>{{ number_format((float)($invoice->tax_total ?? 0), 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2 mt-2">
                        <span class="text-muted">Grand Total</span>
                        <strong>{{ number_format((float)($invoice->grand_total ?? 0), 2) }}</strong>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
