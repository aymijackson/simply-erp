<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        .header {
            margin-bottom: 18px;
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

        .section-title {
            font-size: 13px;
            font-weight: bold;
            margin: 14px 0 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #bbb;
            padding: 6px 8px;
            vertical-align: top;
        }

        th {
            background: #f2f2f2;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .summary {
            width: 42%;
            margin-left: auto;
            margin-top: 14px;
        }

        .no-border td {
            border: none !important;
            padding: 2px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Purchase Order</div>
        <div class="muted">{{ $purchaseOrder->po_no }}</div>
    </div>

    <div class="row">
        <div class="col-half">
            <table class="no-border">
                <tr>
                    <td><strong>Supplier:</strong></td>
                    <td>{{ $purchaseOrder->supplier_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Contact:</strong></td>
                    <td>{{ $purchaseOrder->contact_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>{{ $purchaseOrder->contact_email ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td>{{ $purchaseOrder->contact_phone ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Supplier Ref:</strong></td>
                    <td>{{ $purchaseOrder->supplier_po_ref ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>{{ strtoupper($purchaseOrder->status ?? 'draft') }}</td>
                </tr>
            </table>
        </div>

        <div class="col-half">
            <table class="no-border">
                <tr>
                    <td><strong>PO Date:</strong></td>
                    <td>{{ $purchaseOrder->po_date ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Expected Delivery:</strong></td>
                    <td>{{ $purchaseOrder->expected_delivery_date ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Currency:</strong></td>
                    <td>{{ $purchaseOrder->currency_code ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>FX Rate:</strong></td>
                    <td>{{ $purchaseOrder->fx_rate ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Payment Terms:</strong></td>
                    <td>{{ $purchaseOrder->payment_terms ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Incoterms:</strong></td>
                    <td>{{ $purchaseOrder->incoterms ?? '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-half">
            <table class="no-border">
                <tr>
                    <td><strong>PR No:</strong></td>
                    <td>{{ $purchaseOrder->requisition_no ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>RFQ No:</strong></td>
                    <td>{{ $purchaseOrder->rfq_no ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Quotation No:</strong></td>
                    <td>{{ $purchaseOrder->quotation_no ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Reference:</strong></td>
                    <td>{{ $purchaseOrder->reference ?? '—' }}</td>
                </tr>
            </table>
        </div>

        <div class="col-half">
            <table class="no-border">
                <tr>
                    <td><strong>Delivery Location:</strong></td>
                    <td>{{ $purchaseOrder->delivery_location_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Delivery Store:</strong></td>
                    <td>{{ $purchaseOrder->delivery_store_name ?? '—' }}</td>
                </tr>
                <tr>
                    <td><strong>Bill To Location:</strong></td>
                    <td>{{ $purchaseOrder->bill_to_location_name ?? '—' }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section-title">Notes</div>
    <div style="margin-bottom:10px;">
        {{ $purchaseOrder->notes ?? '—' }}
    </div>

    <div class="section-title">Internal Notes</div>
    <div style="margin-bottom:12px;">
        {{ $purchaseOrder->internal_notes ?? '—' }}
    </div>

    <div class="section-title">PO Lines</div>
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
                <th class="text-end">Shipping</th>
                <th class="text-end">Other</th>
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
                    <td class="text-end">{{ number_format((float)$line->qty, 4) }}</td>
                    <td class="text-end">{{ number_format((float)$line->unit_price, 4) }}</td>
                    <td class="text-end">{{ number_format((float)($line->discount_percent ?? 0), 4) }}</td>
                    <td class="text-end">{{ number_format((float)$line->discount_amount, 2) }}</td>
                    <td>{{ ($line->tax_code_code ? $line->tax_code_code.' - ' : '').($line->tax_code_name ?? '') }}</td>
                    <td class="text-end">{{ number_format((float)($line->tax_rate ?? 0), 4) }}</td>
                    <td class="text-end">{{ number_format((float)$line->tax_amount, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->shipping_amount, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->other_charges_amount, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->line_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" style="text-align:center;">No lines found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary">
        <tr>
            <th>Subtotal</th>
            <td class="text-end">{{ number_format((float)$purchaseOrder->subtotal, 2) }}</td>
        </tr>
        <tr>
            <th>Discount Total</th>
            <td class="text-end">{{ number_format((float)$purchaseOrder->discount_total, 2) }}</td>
        </tr>
        <tr>
            <th>Tax Total</th>
            <td class="text-end">{{ number_format((float)$purchaseOrder->tax_total, 2) }}</td>
        </tr>
        <tr>
            <th>Shipping Total</th>
            <td class="text-end">{{ number_format((float)$purchaseOrder->shipping_total, 2) }}</td>
        </tr>
        <tr>
            <th>Other Charges</th>
            <td class="text-end">{{ number_format((float)$purchaseOrder->other_charges_total, 2) }}</td>
        </tr>
        <tr>
            <th>Total</th>
            <td class="text-end">{{ number_format((float)$purchaseOrder->total_amount, 2) }}</td>
        </tr>
    </table>
</body>
</html>