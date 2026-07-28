@extends('layouts.master')

@section('title', 'Receipt Verification')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-body">
            <h4 class="mb-2">{{ $company['name'] }} — Receipt Verified ✅</h4>
            <div class="text-muted mb-3">This receipt link is valid (signed).</div>

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted">Receipt No</div>
                    <div class="fw-bold">{{ $payment->payment_no ?? ('PAY-'.$payment->id) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted">Customer</div>
                    <div class="fw-bold">{{ $payment->customer?->name ?? ('Customer #'.$payment->customer_id) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted">Amount</div>
                    <div class="fw-bold">{{ number_format((float)$payment->amount_received, 2) }} {{ $payment->currency_code ?? 'NGN' }}</div>
                </div>
            </div>

            <hr>

            <div class="text-muted">Allocations</div>
            <ul class="mb-0">
                @foreach($payment->allocations as $a)
                    <li>
                        {{ $a->invoice?->invoice_no ?? ('INV-'.$a->sales_invoice_id) }}
                        — Applied: {{ number_format((float)$a->amount_applied, 2) }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
