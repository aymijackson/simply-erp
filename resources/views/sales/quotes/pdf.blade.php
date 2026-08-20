<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Quote</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .header { margin-bottom: 20px; }
        .title { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .muted { color: #666; }
        .row { width: 100%; margin-bottom: 10px; }
        .col-half { width: 48%; display: inline-block; vertical-align: top; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 6px 8px; }
        th { background: #f2f2f2; text-align: left; }
        .text-end { text-align: right; }
        .summary { margin-top: 15px; width: 40%; margin-left: auto; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Sales Quote</div>
        <div class="muted">{{ $quote->quote_no }}</div>
    </div>

    <div class="row">
        <div class="col-half">
            <strong>Customer:</strong> {{ $quote->customer->name ?? '—' }}<br>
            <strong>Status:</strong> {{ strtoupper($quote->status ?? 'draft') }}<br>
            <strong>Reference:</strong> {{ $quote->reference ?? '—' }}
        </div>
        <div class="col-half">
            <strong>Quote Date:</strong> {{ optional($quote->quote_date)->format('d M Y') ?? '—' }}<br>
            <strong>Valid Until:</strong> {{ optional($quote->valid_until)->format('d M Y') ?? '—' }}<br>
            <strong>Currency:</strong> {{ $quote->currency_code ?? '—' }}
        </div>
    </div>

    <div style="margin: 12px 0;">
        <strong>Notes:</strong><br>
        {{ $quote->notes ?? '—' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Disc %</th>
                <th class="text-end">Tax %</th>
                <th class="text-end">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quote->lines as $line)
                <tr>
                    <td>{{ $line->variant?->product?->product_name ?? 'Item' }} — {{ $line->variant?->sku ?? ('Variant #'.$line->product_variant_id) }}</td>
                    <td>{{ $line->description ?? '—' }}</td>
                    <td class="text-end">{{ number_format((float)$line->qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->unit_price, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->discount_percent, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->tax_rate, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->line_total, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center;">No lines found</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr><th>Subtotal</th><td class="text-end">{{ number_format((float)$quote->subtotal, 2) }}</td></tr>
        <tr><th>Discount Total</th><td class="text-end">{{ number_format((float)$quote->discount_total, 2) }}</td></tr>
        <tr><th>Tax Total</th><td class="text-end">{{ number_format((float)$quote->tax_total, 2) }}</td></tr>
        <tr><th>Total</th><td class="text-end">{{ number_format((float)$quote->total_amount, 2) }}</td></tr>
    </table>
</body>
</html>
