<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Payment Receipt</title>

    <style>
        body{ font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color:#111; }
        .wrap{ max-width: 800px; margin: 0 auto; }
        .top{ display:flex; justify-content:space-between; align-items:flex-start; }
        .brand h2{ margin:0; font-size:18px; }
        .muted{ color:#666; }
        .box{ border:1px solid #ddd; border-radius:8px; padding:12px; margin-top:12px; }
        table{ width:100%; border-collapse:collapse; }
        th,td{ padding:8px; border-bottom:1px solid #eee; vertical-align:top; }
        th{ text-align:left; background:#fafafa; }
        .text-end{ text-align:right; }
        .badge{ display:inline-block; padding:3px 8px; border-radius:999px; font-size:11px; background:#eee; }
        .totals td{ border:none; padding:4px 8px; }
        .hr{ height:1px; background:#eee; margin:12px 0; }
        @media print { .no-print{ display:none !important; } }
    </style>
</head>
<body>
<div class="wrap">

    <div class="top">
        <div class="brand">
            <h2>{{ config('app.name','THEKAN-ERP') }}</h2>
            <div class="muted">Payment Receipt</div>
        </div>

        <div style="text-align:right">
            <div><b>Receipt No:</b> {{ $payment->payment_no ?? ('PAY-'.$payment->id) }}</div>
            <div><b>Date:</b> {{ $payment->payment_date?->format('d M Y') ?? '-' }}</div>
            <div><b>Status:</b> <span class="badge">{{ strtoupper($payment->status) }}</span></div>
        </div>
    </div>

    <div class="box">
        <table>
            <tr>
                <td style="width:50%">
                    <div class="muted">Received From</div>
                    <div style="font-size:14px"><b>{{ $payment->customer?->name ?? ('Customer #'.$payment->customer_id) }}</b></div>
                </td>
                <td style="width:50%" class="text-end">
                    <div class="muted">Payment Details</div>
                    <div><b>Method:</b> {{ $payment->method ?? '-' }}</div>
                    <div><b>Reference:</b> {{ $payment->reference ?? '-' }}</div>
                    <div><b>Currency:</b> {{ $payment->currency_code ?? 'NGN' }}</div>
                </td>
            </tr>
        </table>

        @if(!empty($payment->remarks))
            <div class="hr"></div>
            <div class="muted">Remarks</div>
            <div>{{ $payment->remarks }}</div>
        @endif
    </div>

    @php
        $amountReceived = (float) $payment->amount_received;
        $allocatedTotal = (float) $payment->allocations->sum('amount_applied');
        $unallocated = max(0, $amountReceived - $allocatedTotal);
    @endphp

    <div class="box">
        <div style="margin-bottom:8px"><b>Allocations</b></div>

        <table>
            <thead>
                <div class="top" style="gap:12px;">
    <div class="brand" style="display:flex; gap:12px; align-items:flex-start;">
        @if(!empty($company['logo_data_uri']))
            <img src="{{ $company['logo_data_uri'] }}" alt="Logo" style="height:52px; width:auto;">
        @endif

        <div>
            <h2 style="margin:0;">{{ $company['name'] }}</h2>
            <div class="muted" style="max-width:420px;">
                {{ $company['address'] }}<br>
                {{ $company['phone'] }} · {{ $company['email'] }}
            </div>
            <div style="margin-top:6px; font-weight:700;">Payment Receipt</div>
        </div>
    </div>

    <div style="text-align:right;">
        <div><b>Receipt No:</b> {{ $payment->payment_no ?? ('PAY-'.$payment->id) }}</div>
        <div><b>Date:</b> {{ $payment->payment_date?->format('d M Y') ?? '-' }}</div>
        <div><b>Status:</b> <span class="badge">{{ strtoupper($payment->status) }}</span></div>

        <div style="margin-top:10px;">
            <img src="{{ $qr }}" alt="QR" style="height:92px; width:92px;">
            <div class="muted" style="font-size:10px; margin-top:4px;">
                Scan to verify receipt
            </div>
        </div>
    </div>
</div>

                <tr>
                    <th style="width:80px">Invoice ID</th>
                    <th>Invoice No</th>
                    <th style="width:110px">Invoice Date</th>
                    <th class="text-end" style="width:120px">Applied</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payment->allocations as $a)
                    <tr>
                        <td>{{ $a->sales_invoice_id }}</td>
                        <td>{{ $a->invoice?->invoice_no ?? ('INV-'.$a->sales_invoice_id) }}</td>
                        <td>{{ $a->invoice?->invoice_date?->format('d M Y') ?? '-' }}</td>
                        <td class="text-end">{{ number_format((float)$a->amount_applied, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted">No allocations.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="hr"></div>

        <table class="totals">
            <tr>
                <td class="text-end"><b>Amount Received:</b></td>
                <td style="width:160px" class="text-end">{{ number_format($amountReceived, 2) }}</td>
            </tr>
            <tr>
                <td class="text-end"><b>Total Allocated:</b></td>
                <td class="text-end">{{ number_format($allocatedTotal, 2) }}</td>
            </tr>
            <tr>
                <td class="text-end"><b>Unallocated:</b></td>
                <td class="text-end">{{ number_format($unallocated, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="muted">Processed By</div>
        <div>
            <b>Posted At:</b> {{ $payment->posted_at ? \Carbon\Carbon::parse($payment->posted_at)->format('d M Y H:i') : '-' }}
            &nbsp; | &nbsp;
            <b>Posted By:</b> {{ $payment->posted_by ?? '-' }}
        </div>
    </div>

    <div class="no-print" style="margin-top:14px; text-align:right">
        <button onclick="window.print()" style="padding:8px 12px;border:1px solid #ddd;border-radius:8px;background:#fff;cursor:pointer;">
            Print
        </button>
    </div>
    <div class="muted" style="font-size:10px; margin-top:10px;">
        Verification link: {{ $verify_url }}
    </div>

</div>
</body>
</html>
