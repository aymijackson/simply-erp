<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Supplier Statement</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body{ font-family: Arial, sans-serif; padding:18px; }
    h2{ margin:0 0 6px; }
    .muted{ color:#666; font-size:12px; }
    table{ width:100%; border-collapse:collapse; margin-top:12px; }
    th,td{ border:1px solid #ccc; padding:8px; font-size:12px; }
    th{ background:#f4f4f4; text-align:left; }
    .r{ text-align:right; }
  </style>
</head>
<body onload="window.print()">
  <h2>Supplier Statement</h2>
  <div class="muted">
    Supplier: <b>{{ $summary['supplier_name'] ?? '—' }}</b><br>
    Range: <b>{{ $summary['from'] ?? '…' }}</b> to <b>{{ $summary['to'] ?? '…' }}</b><br>
    Opening: <b>{{ $summary['opening_balance'] ?? '0.00' }}</b> |
    Closing: <b>{{ $summary['closing_balance'] ?? '0.00' }}</b>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:110px;">Date</th>
        <th style="width:90px;">Type</th>
        <th style="width:160px;">Ref</th>
        <th>Memo</th>
        <th class="r" style="width:120px;">Debit</th>
        <th class="r" style="width:120px;">Credit</th>
        <th class="r" style="width:120px;">Balance</th>
      </tr>
    </thead>
    <tbody>
      @forelse($lines as $r)
        <tr>
          <td>{{ $r['date'] ?? '' }}</td>
          <td>{{ $r['type'] ?? '' }}</td>
          <td>{{ $r['ref'] ?? '' }}</td>
          <td>{{ $r['memo'] ?? '' }}</td>
          <td class="r">{{ $r['debit'] ?? '' }}</td>
          <td class="r">{{ $r['credit'] ?? '' }}</td>
          <td class="r"><b>{{ $r['balance'] ?? '' }}</b></td>
        </tr>
      @empty
        <tr><td colspan="7" class="muted">No transactions.</td></tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>