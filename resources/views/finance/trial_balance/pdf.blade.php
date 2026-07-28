<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name') }} - Trial Balance</title>
    <style>
        @page {
            margin: 90px 30px 60px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
            margin: 0;
        }

        header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            height: 55px;
            border-bottom: 2px solid #1f4e78;
        }

        footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            height: 24px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
            padding-top: 6px;
        }

        .left { float: left; }
        .right { float: right; }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #1f4e78;
            margin: 0 0 4px 0;
        }

        .report-title {
            font-size: 15px;
            font-weight: bold;
            margin: 0;
        }

        .meta {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .filters-table,
        .summary-table,
        .report-table {
            margin-bottom: 14px;
        }

        .filters-table td,
        .summary-table td,
        .report-table th,
        .report-table td {
            border: 1px solid #d9d9d9;
            padding: 6px 8px;
        }

        .filters-table .label,
        .summary-table .label {
            width: 120px;
            font-weight: bold;
            background: #f3f6fa;
        }

        .summary-table .value {
            text-align: right;
            font-weight: bold;
        }

        .report-table thead th {
            background: #f3f6fa;
            font-weight: bold;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .balanced {
            color: #198754;
            font-weight: bold;
        }

        .not-balanced {
            color: #dc3545;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            color: #666;
            padding: 16px 0;
        }
    </style>
</head>
<body>
    <header>
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="report-title">Trial Balance</div>
        <div class="meta">Generated on {{ now()->format('d-m-Y H:i') }}</div>
    </header>

    <footer>
        <div class="left">{{ config('app.name') }} • Finance Reports</div>
        <div class="right">Page <span class="pagenum"></span></div>
    </footer>

    <table class="filters-table">
        <tr>
            <td class="label">Status</td>
            <td>{{ $report['filters']['status_label'] }}</td>
            <td class="label">From Date</td>
            <td>{{ $report['filters']['date_from'] ?: '—' }}</td>
            <td class="label">To Date</td>
            <td>{{ $report['filters']['date_to'] ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Non-zero only</td>
            <td>{{ $report['filters']['nonzero'] ? 'Yes' : 'No' }}</td>
            <td class="label">Search</td>
            <td colspan="3">{{ $report['filters']['q'] ?: '—' }}</td>
        </tr>
    </table>

    <table class="summary-table">
        <tr>
            <td class="label">Total Debit</td>
            <td class="value">{{ number_format($report['summary']['sum_debit'], 2) }}</td>
            <td class="label">Total Credit</td>
            <td class="value">{{ number_format($report['summary']['sum_credit'], 2) }}</td>
        </tr>
        <tr>
            <td class="label">Difference</td>
            <td class="value">{{ number_format($report['summary']['diff'], 2) }}</td>
            <td class="label">Status</td>
            <td class="value {{ $report['summary']['balanced'] ? 'balanced' : 'not-balanced' }}">
                {{ $report['summary']['balanced'] ? 'BALANCED' : 'NOT BALANCED' }}
            </td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th>Account</th>
                <th style="width: 140px;" class="text-end">Debit</th>
                <th style="width: 140px;" class="text-end">Credit</th>
                <th style="width: 140px;" class="text-end">Net</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['rows'] as $row)
                <tr>
                    <td>{{ $row['account'] }}</td>
                    <td class="text-end">{{ number_format($row['debit_raw'], 2) }}</td>
                    <td class="text-end">{{ number_format($row['credit_raw'], 2) }}</td>
                    <td class="text-end">{{ number_format($row['net_raw'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty">No trial balance rows found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
            $font = $fontMetrics->getFont("DejaVu Sans", "normal");
            $size = 9;
            $width = $fontMetrics->getTextWidth($text, $font, $size);
            $x = 760 - $width;
            $y = 570;
            $pdf->page_text($x, $y, $text, $font, $size, [0.4, 0.4, 0.4]);
        }
    </script>
</body>
</html>