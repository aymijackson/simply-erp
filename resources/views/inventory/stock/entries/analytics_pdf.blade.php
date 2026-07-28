<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body{ font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h2{ margin:0 0 10px; }
    table{ width:100%; border-collapse: collapse; }
    th,td{ border:1px solid #ddd; padding:6px; vertical-align: top; }
    th{ background:#f3f3f3; }
    .muted{ color:#777; }
  </style>
</head>
<body>
  <h2>Stock Entry Analytics Report</h2>
  <p class="muted">
    Filters:
    From: {{ $filters['from'] ?? 'N/A' }},
    To: {{ $filters['to'] ?? 'N/A' }},
    Store: {{ $filters['store_id'] ?? 'All' }},
    Status: {{ $filters['status'] ?? 'All' }},
    Type: {{ $filters['entry_type'] ?? 'All' }}
  </p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Date</th>
        <th>Store</th>
        <th>Type</th>
        <th>Status</th>
        <th>Party</th>
        <th>Lines</th>
      </tr>
    </thead>
    <tbody>
      @foreach($entries as $e)
        <tr>
          <td>{{ $e->reference ?? $e->id }}</td>
          <td>{{ \Carbon\Carbon::parse($e->entry_date)->format('d-m-Y') }}</td>
          <td>{{ $e->store?->name }}</td>
          <td>{{ $e->entry_type }}</td>
          <td>{{ $e->status }}</td>
          <td>
            @if($e->entry_type === 'cust_return')
              Customer: {{ $e->customer?->name }}
            @else
              Supplier: {{ $e->supplier?->name }}
            @endif
          </td>
          <td>
            @foreach($e->lines as $l)
              - {{ $l->product_variant?->sku }} ({{ $l->qty }})<br>
            @endforeach
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
