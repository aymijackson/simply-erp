<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_no ?? ('INV-'.$invoice->id) }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .row { display:flex; justify-content:space-between; }
        .muted { color:#666; }
        table { width:100%; border-collapse: collapse; margin-top:12px; }
        th, td { border:1px solid #ddd; padding:8px; }
        th { background:#f3f3f3; text-align:left; }
        .text-right { text-align:right; }
        .total-box { width: 280px; float:right; margin-top:10px; }
        .total-box td { border:none; padding:4px 0; }
        .total-box .top { border-top:1px solid #ddd; padding-top:8px; }
    </style>
</head>
<body>
    <div class="row">
        <div>
            <h2 style="margin:0;">INVOICE</h2>
            <div class="muted">Invoice No: <strong>{{ $invoice->invoice_no ?? ('INV-'.$invoice->id) }}</strong></div>
            <div class="muted">Invoice Date: <strong>{{ $invoice->invoice_date?->format('d M Y') ?? '-' }}</strong></div>
            <div class="muted">Order: <strong>{{ $invoice->order?->order_no ?? '-' }}</strong></div>
        </div>
        <div style="text-align:right;">
            <div class="muted">Status: <strong>{{ strtoupper($invoice->status) }}</strong></div>
            <div class="muted">Currency: <strong>{{ $invoice->currency_code ?? '-' }}</strong></div>
        </div>
    </div>

    <hr>

    <div>
        <div class="muted">Bill To</div>
        <div><strong>{{ $invoice->customer?->name ?? ('Customer #'.$invoice->customer_id) }}</strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th>Item</th>
                <th class="text-right" style="width:120px;">Qty</th>
                <th class="text-right" style="width:140px;">Unit Price</th>
                <th class="text-right" style="width:140px;">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lines as $i => $ln)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>
                    @if(($ln->line_type ?? 'product') === 'product')
                        <strong>{{ $ln->variant?->product?->product_name ?? 'Item' }}</strong><br>
                        <span class="muted">{{ $ln->variant?->sku ?? ('Variant #'.$ln->product_variant_id) }}</span>
                    @else
                        <strong>{{ strtoupper($ln->charge_code) }}</strong><br>
                        <span class="muted">{{ $ln->description ?? '-' }}</span>
                    @endif
                </td>
                <td class="text-right">{{ number_format((float)$ln->qty_to_invoice, 4) }}</td>
                <td class="text-right">{{ number_format((float)$ln->unit_price, 4) }}</td>
                <td class="text-right">{{ number_format((float)$ln->line_total, 4) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="total-box">
        <tr>
            <td class="muted">Subtotal</td>
            <td class="text-right"><strong>{{ number_format((float)($invoice->subtotal ?? 0), 2) }}</strong></td>
        </tr>
        <tr>
            <td class="muted">Tax</td>
            <td class="text-right"><strong>{{ number_format((float)($invoice->tax_total ?? 0), 2) }}</strong></td>
        </tr>
        <tr>
            <td class="top muted">Grand Total</td>
            <td class="top text-right"><strong>{{ number_format((float)($invoice->grand_total ?? 0), 2) }}</strong></td>
        </tr>
    </table>

    <div style="clear:both;"></div>

    @if(!empty($invoice->remarks))
        <p><strong>Remarks:</strong> {{ $invoice->remarks }}</p>
    @endif
</body>
</html>
