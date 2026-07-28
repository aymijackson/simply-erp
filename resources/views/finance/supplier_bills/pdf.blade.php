<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Supplier Bill - {{ $bill->bill_no ?? ('BILL-'.$bill->id) }}</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
    .header { margin-bottom: 20px; }
    .title { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
    .muted { color: #666; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    th, td { border: 1px solid #ccc; padding: 6px; vertical-align: top; }
    th { background: #f2f2f2; text-align: left; }
    .text-end { text-align: right; }
    .summary { margin-top: 18px; width: 40%; margin-left: auto; }
    .summary td { border: 1px solid #ccc; }
  </style>
</head>
<body>

  <div class="header">
    <div class="title">Supplier Bill</div>
    <div class="muted">Document: {{ $bill->bill_no ?? ('BILL-'.$bill->id) }}</div>
  </div>

  <table>
    <tr>
      <th style="width:20%">Bill No</th>
      <td>{{ $bill->bill_no ?? ('BILL-'.$bill->id) }}</td>
      <th style="width:20%">Status</th>
      <td>{{ strtoupper($bill->status ?? 'draft') }}</td>
    </tr>
    <tr>
      <th>Bill Date</th>
      <td>{{ $bill->bill_date ?? '—' }}</td>
      <th>Due Date</th>
      <td>{{ $bill->due_date ?? '—' }}</td>
    </tr>
    <tr>
      <th>Supplier</th>
      <td>{{ $bill->supplier_name ?? '—' }}</td>
      <th>Vendor Name</th>
      <td>{{ $bill->vendor_name ?? '—' }}</td>
    </tr>
    <tr>
      <th>Currency</th>
      <td>{{ $bill->currency_code ?? '—' }}</td>
      <th>FX Rate</th>
      <td>{{ $bill->fx_rate ?? '—' }}</td>
    </tr>
    <tr>
      <th>Reference</th>
      <td>{{ $bill->reference ?? '—' }}</td>
      <th>Payable Account</th>
      <td>{{ trim(($bill->payable_code ?? '').' - '.($bill->payable_name ?? '')) ?: '—' }}</td>
    </tr>
    <tr>
      <th>Memo</th>
      <td colspan="3">{{ $bill->memo ?? '—' }}</td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>GL Account</th>
        <th>Description</th>
        <th class="text-end">Qty</th>
        <th class="text-end">Unit Cost</th>
        <th class="text-end">Tax Rate</th>
        <th class="text-end">Tax Amount</th>
        <th class="text-end">Line Total</th>
      </tr>
    </thead>
    <tbody>
      @forelse($lines as $line)
        <tr>
          <td>{{ trim(($line->gl_code ?? '').' - '.($line->gl_name ?? '')) ?: '—' }}</td>
          <td>{{ $line->description ?? '—' }}</td>
          <td class="text-end">{{ number_format((float)($line->qty ?? 0), 2) }}</td>
          <td class="text-end">{{ number_format((float)($line->unit_cost ?? 0), 2) }}</td>
          <td class="text-end">{{ number_format((float)($line->tax_rate ?? 0), 2) }}</td>
          <td class="text-end">{{ number_format((float)($line->tax_amount ?? 0), 2) }}</td>
          <td class="text-end">{{ number_format((float)($line->line_total ?? 0), 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="text-end">No lines found</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <table class="summary">
    <tr>
      <th>Subtotal</th>
      <td class="text-end">{{ number_format((float)($bill->subtotal ?? 0), 2) }}</td>
    </tr>
    <tr>
      <th>Tax Total</th>
      <td class="text-end">{{ number_format((float)($bill->tax_total ?? 0), 2) }}</td>
    </tr>
    <tr>
      <th>Total Amount</th>
      <td class="text-end">{{ number_format((float)($bill->total_amount ?? 0), 2) }}</td>
    </tr>
    <tr>
      <th>Amount Paid</th>
      <td class="text-end">{{ number_format((float)($bill->amount_paid ?? 0), 2) }}</td>
    </tr>
    <tr>
      <th>Balance Due</th>
      <td class="text-end">{{ number_format((float)($bill->balance_due ?? 0), 2) }}</td>
    </tr>
  </table>

</body>
</html>