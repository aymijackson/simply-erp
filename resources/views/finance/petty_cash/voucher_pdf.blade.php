<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Petty Cash Voucher</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        .wrapper { width: 100%; }
        .title { text-align: center; font-size: 20px; font-weight: bold; margin-bottom: 5px; }
        .subtitle { text-align: center; font-size: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { border: 1px solid #999; padding: 8px; vertical-align: top; }
        th { background: #f2f2f2; text-align: left; }
        .no-border td { border: none; padding: 3px 0; }
        .section-title { font-size: 14px; font-weight: bold; margin: 12px 0 6px; }
        .signature-box { height: 55px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="title">PETTY CASH VOUCHER</div>
    <div class="subtitle">{{ config('app.name', 'Simply-ERP') }}</div>

    <table>
        <tr>
            <th width="20%">Voucher No</th>
            <td width="30%">{{ $row->voucher_no }}</td>
            <th width="20%">Transaction No</th>
            <td width="30%">{{ $row->transaction_no }}</td>
        </tr>
        <tr>
            <th>Date</th>
            <td>{{ optional($row->transaction_date)->format('Y-m-d') }}</td>
            <th>Status</th>
            <td>{{ strtoupper($row->status) }}</td>
        </tr>
        <tr>
            <th>Petty Cash Account</th>
            <td>{{ $row->account->name ?? '-' }}</td>
            <th>Type</th>
            <td>{{ strtoupper($row->type) }}</td>
        </tr>
        <tr>
            <th>Payee</th>
            <td>{{ $row->payee ?? '-' }}</td>
            <th>Reference No</th>
            <td>{{ $row->reference_no ?? '-' }}</td>
        </tr>
        <tr>
            <th>Expense Account</th>
            <td colspan="3">{{ $row->expenseAccount->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>Description</th>
            <td colspan="3">{{ $row->description ?? '-' }}</td>
        </tr>
        <tr>
            <th>Amount</th>
            <td colspan="3"><strong>{{ number_format($row->amount, 2) }}</strong></td>
        </tr>
    </table>

    <div class="section-title">Workflow Details</div>
    <table>
        <tr>
            <th width="25%">Submitted By</th>
            <td width="25%">{{ optional($row->submittedBy)->name ?? '-' }}</td>
            <th width="25%">Submitted At</th>
            <td width="25%">{{ optional($row->submitted_at)->format('Y-m-d H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Approved By</th>
            <td>{{ optional($row->approvedBy)->name ?? '-' }}</td>
            <th>Approved At</th>
            <td>{{ optional($row->approved_at)->format('Y-m-d H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Posted By</th>
            <td>{{ optional($row->postedBy)->name ?? '-' }}</td>
            <th>Posted At</th>
            <td>{{ optional($row->posted_at)->format('Y-m-d H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Approval Notes</th>
            <td colspan="3">{{ $row->approval_notes ?? '-' }}</td>
        </tr>
    </table>

    <div class="section-title">Sign-off</div>
    <table>
        <tr>
            <th>Prepared By</th>
            <th>Approved By</th>
            <th>Received By</th>
        </tr>
        <tr>
            <td class="signature-box"></td>
            <td class="signature-box"></td>
            <td class="signature-box"></td>
        </tr>
    </table>
</div>
</body>
</html>