<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payslip</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        .section h3 { border-bottom: 1px solid #ccc; padding-bottom: 5px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>

    <div class="header">
        <h2>Payroll Slip</h2>
        <p><strong>Month:</strong> {{ \Carbon\Carbon::parse($payroll->pay_date)->format('F Y') }}</p>
    </div>

    <div class="section">
        <h3>Employee Info</h3>
        <p><strong>Name:</strong> {{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}</p>
        <p><strong>Email:</strong> {{ $payroll->employee->email }}</p>
        <p><strong>Payment Status:</strong> {{ ucfirst($payroll->payment_status) }}</p>
    </div>

    <div class="section">
        <h3>Salary Breakdown</h3>
        <table>
            <tr>
                <th>Basic Salary</th>
                <td>NGN {{ number_format($payroll->basic_salary, 2) }}</td>
            </tr>
            <tr>
                <th>Total Allowances</th>
                <td>NGN {{ number_format($payroll->total_allowances, 2) }}</td>
            </tr>
            <tr>
                <th>Total Deductions</th>
                <td>NGN {{ number_format($payroll->total_deductions, 2) }}</td>
            </tr>
            <tr>
                <th>Net Salary</th>
                <td><strong>NGN {{ number_format($payroll->net_salary, 2) }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Allowances</h3>
        <table>
            <thead><tr><th>Type</th><th>Amount</th></tr></thead>
            <tbody>
                @forelse ($payroll->allowances as $a)
                    <tr><td>{{ $a->type }}</td><td>NGN {{ number_format($a->amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="2">No allowances</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h3>Deductions</h3>
        <table>
            <thead><tr><th>Type</th><th>Amount</th></tr></thead>
            <tbody>
                @forelse ($payroll->deductions as $d)
                    <tr><td>{{ $d->type }}</td><td>NGN {{ number_format($d->amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="2">No deductions</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

</body>
</html>
