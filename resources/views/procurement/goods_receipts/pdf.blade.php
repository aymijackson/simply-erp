<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Goods Receipt - {{ $grn->grn_no }}</title>
    <style>
        body{
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
        }
        .header{
            margin-bottom: 20px;
        }
        .title{
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .subtitle{
            color: #666;
            margin-bottom: 15px;
        }
        .table{
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th,
        .table td{
            border: 1px solid #ccc;
            padding: 6px;
            vertical-align: top;
        }
        .table th{
            background: #f3f3f3;
            text-align: left;
        }
        .text-end{
            text-align: right;
        }
        .meta td{
            border: none;
            padding: 4px 0;
        }
        .section-title{
            margin-top: 20px;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Goods Receipt Note</div>
        <div class="subtitle">{{ $grn->grn_no }}</div>
    </div>

    <table class="meta" width="100%">
        <tr>
            <td width="25%"><strong>GRN No:</strong></td>
            <td width="25%">{{ $grn->grn_no }}</td>
            <td width="25%"><strong>Receipt Date:</strong></td>
            <td width="25%">{{ $grn->receipt_date }}</td>
        </tr>
        <tr>
            <td><strong>Supplier:</strong></td>
            <td>{{ $grn->supplier->name ?? '—' }}</td>
            <td><strong>PO No:</strong></td>
            <td>{{ $grn->purchaseOrder->po_number ?? $grn->purchaseOrder->po_no ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Delivery Note:</strong></td>
            <td>{{ $grn->supplier_delivery_note_no ?? '—' }}</td>
            <td><strong>Status:</strong></td>
            <td>{{ strtoupper($grn->status) }}</td>
        </tr>
        <tr>
            <td><strong>Location:</strong></td>
            <td>{{ $grn->deliveryLocation->name ?? '—' }}</td>
            <td><strong>Store:</strong></td>
            <td>{{ $grn->deliveryStore->name ?? '—' }}</td>
        </tr>
        <tr>
            <td><strong>Reference:</strong></td>
            <td>{{ $grn->reference ?? '—' }}</td>
            <td><strong>Subtotal:</strong></td>
            <td>{{ number_format((float)$grn->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Notes:</strong></td>
            <td colspan="3">{{ $grn->notes ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Receipt Lines</div>

    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Variant</th>
                <th>Description</th>
                <th>Unit</th>
                <th class="text-end">Ordered</th>
                <th class="text-end">Prev. Received</th>
                <th class="text-end">Received</th>
                <th class="text-end">Accepted</th>
                <th class="text-end">Rejected</th>
                <th class="text-end">Damaged</th>
                <th class="text-end">Unit Cost</th>
                <th class="text-end">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grn->lines as $line)
                <tr>
                    <td>{{ ($line->product->product_code ?? '') }} {{ $line->product->product_name ?? '—' }}</td>
                    <td>
                        {{ $line->productVariant->sku ?? '—' }}
                        @if(!empty($line->productVariant->item_type))
                            ({{ strtoupper($line->productVariant->item_type) }})
                        @endif
                    </td>
                    <td>{{ $line->description ?? '—' }}</td>
                    <td>
                        {{ $line->unit->name ?? '—' }}
                        @if(!empty($line->unit->symbol))
                            ({{ $line->unit->symbol }})
                        @endif
                    </td>
                    <td class="text-end">{{ number_format((float)$line->ordered_qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->previously_received_qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->received_qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->accepted_qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->rejected_qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->damage_qty, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->unit_cost, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->line_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="text-center">No lines found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>