{{-- resources/views/exports/stock-entries.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
<h2>Stock Report</h2>
<p>Generated: {{ now()->format('Y-m-d H:i') }}</p>

<table>
    <thead>
        <tr>
            <th>#</th><th>Ref</th><th>Date</th><th>Store</th><th>Type</th>
            <th>Supplier</th><th>Customer</th><th>Status</th>
            <th style="text-align:right;">Qty</th>
            <th style="text-align:right;">Value</th>
        </tr>
    </thead>
    <tbody>
        @foreach($entries as $i => $e)
            <tr>
                <td>{{ $i+1 }}</td>
                <td>{{ $e->reference }}</td>
                <td>{{ date('d-m-Y', strtotime($e->entry_date)) }}</td>
                <td>{{ $e->store->name }}</td>
                <td>{{ $e->entry_type === 'cust_return' ? 'Return' : 'Normal' }}</td>
                <td>{{ optional($e->supplier)->name }}</td>
                <td>{{ optional($e->customer)->name }}</td>
                <td>{{ ucfirst($e->status) }}</td>
                <td style=\"text-align:right;\">{{ $e->lines->sum('qty') }}</td>
                <td style=\"text-align:right;\">{{ number_format(
                        $e->lines->sum(fn ($l) => $l->qty * $l->unit_cost), 2) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>
