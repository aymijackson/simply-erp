<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Requisition PDF</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .header { margin-bottom: 20px; }
        .title { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
        .sub { color: #666; margin-bottom: 12px; }
        .meta-table, .line-table { width: 100%; border-collapse: collapse; }
        .meta-table td { padding: 6px 4px; vertical-align: top; }
        .line-table th, .line-table td {
            border: 1px solid #ccc;
            padding: 6px;
            vertical-align: top;
        }
        .line-table th { background: #f2f2f2; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .mt-20 { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Purchase Requisition</div>
        <div class="sub">{{ $requisition->requisition_no ?? ('REQ-'.$requisition->id) }}</div>
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>Date:</strong> {{ $requisition->requisition_date }}</td>
            <td><strong>Needed By:</strong> {{ $requisition->needed_by_date ?? '—' }}</td>
            <td><strong>Priority:</strong> {{ ucfirst($requisition->priority ?? 'normal') }}</td>
        </tr>
        <tr>
            <td><strong>Status:</strong> {{ ucfirst($requisition->status ?? 'draft') }}</td>
            <td><strong>Requested By:</strong> {{ $requisition->requested_by_name ?? '—' }}</td>
            <td><strong>Approved By:</strong> {{ $requisition->approved_by_name ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="3"><strong>Reference:</strong> {{ $requisition->reference ?? '—' }}</td>
        </tr>
        <tr>
            <td colspan="3"><strong>Notes:</strong> {{ $requisition->notes ?? '—' }}</td>
        </tr>
    </table>

    <table class="line-table mt-20">
        <thead>
            <tr>
                <th>Product</th>
                <th>Description</th>
                <th>Unit</th>
                <th class="text-end">Qty</th>
                <th class="text-end">Est. Unit Cost</th>
                <th>Tax Code</th>
                <th class="text-end">Tax %</th>
                <th class="text-end">Tax Amt</th>
                <th class="text-end">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $line)
                <tr>
                    <td>{{ trim(($line->product_code ? $line->product_code.' - ' : '').($line->product_name ?? '—')) }}</td>
                    <td>{{ $line->description ?? '—' }}</td>
                    <td>{{ $line->unit_name ?? '—' }}</td>
                    <td class="text-end">{{ number_format((float)$line->qty, 4) }}</td>
                    <td class="text-end">{{ number_format((float)$line->estimated_unit_cost, 4) }}</td>
                    <td>{{ trim(($line->tax_code_code ? $line->tax_code_code.' - ' : '').($line->tax_code_name ?? '—')) }}</td>
                    <td class="text-end">{{ number_format((float)($line->tax_rate ?? 0), 4) }}</td>
                    <td class="text-end">{{ number_format((float)$line->tax_amount, 2) }}</td>
                    <td class="text-end">{{ number_format((float)$line->line_total, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;">No lines found</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="8" class="text-end fw-bold">Subtotal</td>
                <td class="text-end fw-bold">{{ number_format((float)$requisition->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td colspan="8" class="text-end fw-bold">Tax Total</td>
                <td class="text-end fw-bold">{{ number_format((float)$requisition->tax_total, 2) }}</td>
            </tr>
            <tr>
                <td colspan="8" class="text-end fw-bold">Grand Total</td>
                <td class="text-end fw-bold">{{ number_format((float)$requisition->total_amount, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>