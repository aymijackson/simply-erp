<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Delivery Note - {{ $delivery->delivery_no }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }

        .header {
            width: 100%;
            margin-bottom: 15px;
        }

        .header td {
            vertical-align: top;
        }

        .title {
            font-size: 20px;
            font-weight: bold;
        }

        .muted {
            color: #666;
        }

        .box {
            border: 1px solid #ccc;
            padding: 8px;
        }

        table.lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table.lines th,
        table.lines td {
            border: 1px solid #ccc;
            padding: 6px;
        }

        table.lines th {
            background: #f2f2f2;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .mt-20 { margin-top: 20px; }
        .mt-30 { margin-top: 30px; }
        .mt-40 { margin-top: 40px; }

        .sign-box {
            width: 32%;
            display: inline-block;
            text-align: center;
            margin-top: 40px;
        }

        .sign-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 10px;
            text-align: center;
            color: #777;
        }
    </style>
</head>

<body>

{{-- ================= HEADER ================= --}}
<table class="header">
    <tr>
        <td width="60%">
            <div class="title">DELIVERY NOTE</div>
            <div class="muted">Document #: {{ $delivery->delivery_no }}</div>
            <div class="muted">Status: {{ strtoupper($delivery->status) }}</div>
        </td>

        <td width="40%" class="text-right">
            {{-- Replace with your company info --}}
            <strong>{{ config('app.name') }}</strong><br>
            Your Company Address<br>
            City, Country<br>
            Phone: +000 000 000<br>
            Email: info@company.com
        </td>
    </tr>
</table>

{{-- ================= DELIVERY + ORDER INFO ================= --}}
<table width="100%" cellpadding="4">
    <tr>
        <td width="50%" class="box">
            <strong>Delivery Info</strong><br>
            Delivery No: {{ $delivery->delivery_no }}<br>
            Ship Date: {{ optional($delivery->ship_date)->format('d M Y') }}<br>
            Delivered At: {{ optional($delivery->delivered_at)->format('d M Y H:i') }}<br>
            Order No: {{ $delivery->order?->order_no ?? '-' }}
        </td>

        <td width="50%" class="box">
            <strong>Customer</strong><br>
            {{ $delivery->customer?->name }}<br>
            {{ $delivery->customer?->email }}<br>
            {{ $delivery->customer?->phone }}
        </td>
    </tr>
</table>

{{-- ================= LOGISTICS INFO ================= --}}
<table width="100%" cellpadding="4" class="mt-20">
    <tr>
        <td width="33%" class="box">
            <strong>Driver</strong><br>
            {{ $delivery->driver?->full_name ?? '-' }}
        </td>

        <td width="33%" class="box">
            <strong>Vehicle</strong><br>
            {{ $delivery->vehicle?->registration_no ?? '-' }}
        </td>

        <td width="33%" class="box">
            <strong>Default Store</strong><br>
            {{ $delivery->store?->name ?? '-' }}
        </td>
    </tr>
</table>

{{-- ================= LINES TABLE ================= --}}
<table class="lines">
    <thead>
        <tr>
            <th width="5%">#</th>
            <th width="35%">Item</th>
            <th width="20%">SKU</th>
            <th width="10%" class="text-right">Qty To Deliver</th>
            <th width="10%" class="text-right">Qty Delivered</th>
            <th width="20%">Store</th>
        </tr>
    </thead>

    <tbody>
        @foreach($delivery->lines as $i => $ln)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>

                <td>
                    {{ $ln->variant?->product?->product_name ?? 'Item' }}
                </td>

                <td>
                    {{ $ln->variant?->sku ?? '-' }}
                </td>

                <td class="text-right">
                    {{ number_format($ln->qty_to_deliver, 4) }}
                </td>

                <td class="text-right">
                    {{ number_format($ln->qty_delivered_actual, 4) }}
                </td>

                <td>
                    {{ $ln->store?->name ?? '-' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- ================= REMARKS ================= --}}
@if($delivery->remarks)
<div class="mt-20 box">
    <strong>Remarks</strong><br>
    {{ $delivery->remarks }}
</div>
@endif

{{-- ================= SIGNATURES ================= --}}
<div class="mt-30">
    <div class="sign-box">
        <div class="sign-line">Prepared By</div>
    </div>

    <div class="sign-box">
        <div class="sign-line">Driver Signature</div>
    </div>

    <div class="sign-box">
        <div class="sign-line">Customer Signature</div>
    </div>
</div>

{{-- ================= FOOTER ================= --}}
<div class="footer">
    Delivery Note • Generated {{ now()->format('d M Y H:i') }}
</div>

</body>
</html>
