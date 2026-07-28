<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Supplier Quotation</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }
        .header {
            margin-bottom: 20px;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .muted {
            color: #666;
        }
        .row {
            width: 100%;
            margin-bottom: 10px;
        }
        .col-half {
            width: 48%;
            display: inline-block;
            vertical-align: top;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #bbb;
            padding: 6px 8px;
        }
        th {
            background: #f2f2f2;
            text-align: left;
        }
        .text-end {
            text-align: right;
        }
        .summary {
            margin-top: 15px;
            width: 40%;
            margin-left: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Supplier Quotation</div>
        <div class="muted">{{ $quotation->quotation_no }}</div>
    </div>

    <div class="row">
        <div class="col-half">
            <strong>Supplier:</strong> {{ $quotation->supplier_name ?? '—' }}<br>
            <strong>RFQ No:</strong> {{ $quotation->rfq_no ?? '—' }}<br>
            <strong>Supplier Quote No:</strong> {{ $quotation->supplier_quote_no ?? '—' }}<br>
            <strong>Status:</strong> {{ strtoupper($quotation->status ?? 'draft') }}
        </div>
        <div class="col-half">
            <strong>Quotation Date:</strong> {{ $quotation->quotation_date ?? '—' }}<br>
            <strong>Valid Until:</strong> {{ $quotation->valid_until ?? '—' }}<br>
            <strong>Currency:</strong> {{ $quotation->currency_code ?? '—' }}<br>
            <strong>Reference:</strong> {{ $quotation->reference ?? '—' }}
        </div>
    </div>

    <div style="margin: 12px 0;">
        <strong>Notes:</strong><br>
        {{ $quotation->notes ?? '—' }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Description</th>
                <th>Unit</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Unit Price</th>
                <th class="text-end">Disc %</th>
                <th class="text-end">Disc Amt</th>
                <th>Tax Code</th>
                <th class="text-end">Tax %</th>
                <th class="text-end">Tax Amt</th>
                <th class="text-end">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $line)
                <tr>
                    <td>{{ ($line->product_code ? $line->product_code.' - ' : '').($line->product_name ?? '') }}</td>
                    <td>{{ $line->description ?? '—' }}</td>
                    <td>
                        {{ $line->unit_name ?? '—' }}
                        @if(!empty($line->unit_symbol))
                            ({{ $line->unit_symbol }})
                        @endif
                    </td>
                    <td class="text-end">{{ number_format((float)$line->qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->unit_price, 2) }}</td>
                    <td class="text-end">{{ number_format((float)($line->discount_percent ?? 0), 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->discount_amount, 2) }}</td>
                    <td>{{ ($line->tax_code_code ? $line->tax_code_code.' - ' : '').($line->tax_code_name ?? '') }}</td>
                    <td class="text-end">{{ number_format((float)($line->tax_rate ?? 0), 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->tax_amount, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->line_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" style="text-align:center;">No lines found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <th>Subtotal</th>
            <td class="text-end">{{ number_format((float)$quotation->subtotal, 2) }}</td>
        </tr>
        <tr>
            <th>Discount Total</th>
            <td class="text-end">{{ number_format((float)$quotation->discount_total, 2) }}</td>
        </tr>
        <tr>
            <th>Tax Total</th>
            <td class="text-end">{{ number_format((float)$quotation->tax_total, 2) }}</td>
        </tr>
        <tr>
            <th>Total</th>
            <td class="text-end">{{ number_format((float)$quotation->total_amount, 2) }}</td>
        </tr>
    </table>
</body>
</html>