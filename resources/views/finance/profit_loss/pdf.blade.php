<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name') }} - Profit & Loss</title>
    <style>
        @page { margin: 90px 25px 60px 25px; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        header {
            position: fixed;
            top: -70px;
            left: 0;
            right: 0;
            border-bottom: 2px solid #1f4e78;
            padding-bottom: 8px;
        }

        footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
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
        }

        .report-title {
            font-size: 15px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        th, td {
            border: 1px solid #d9d9d9;
            padding: 6px 8px;
        }

        th {
            background: #f3f6fa;
            text-align: left;
        }

        .text-end { text-align: right; }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1f4e78;
            margin: 14px 0 6px 0;
        }

        .summary td.label {
            background: #f3f6fa;
            font-weight: bold;
            width: 140px;
        }

        .summary td.value {
            text-align: right;
            font-weight: bold;
        }

        .group-row {
            background: #f8f9fc;
            font-weight: bold;
        }

        .child-name {
            padding-left: 24px;
        }
    </style>
</head>
<body>
<header>
    <div class="company-name">{{ config('app.name') }}</div>
    <div class="report-title">Profit &amp; Loss</div>
    <div>Period: {{ $report['filters']['current_period_label'] }}</div>
</header>

<footer>
    <div class="left">{{ config('app.name') }} • Finance Reports</div>
    <div class="right">Page <span class="pagenum"></span></div>
</footer>

<table class="summary">
    <tr>
        <td class="label">Revenue</td>
        <td class="value">{{ number_format($report['meta']['income'], 2) }}</td>
        <td class="label">Gross Profit</td>
        <td class="value">{{ number_format($report['meta']['grossProfit'], 2) }}</td>
    </tr>
    <tr>
        <td class="label">Cost of Sales</td>
        <td class="value">{{ number_format($report['meta']['cogs'], 2) }}</td>
        <td class="label">Expenses</td>
        <td class="value">{{ number_format($report['meta']['expenses'], 2) }}</td>
    </tr>
    <tr>
        <td class="label">Net Profit</td>
        <td class="value">{{ number_format($report['meta']['netProfit'], 2) }}</td>
        <td class="label">Previous Net Profit</td>
        <td class="value">{{ number_format($report['meta']['previous_netProfit'], 2) }}</td>
    </tr>
</table>

@foreach(['income' => 'Income', 'cogs' => 'Cost of Sales (COGS)', 'expenses' => 'Expenses'] as $key => $title)
    <div class="section-title">{{ $title }}</div>

    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th class="text-end" style="width: 150px;">Amount</th>
            </tr>
        </thead>
        <tbody>
        @forelse($report['sections'][$key] as $group)
            <tr class="group-row">
                <td>{{ $group['group_code'] }} - {{ $group['group_name'] }}</td>
                <td class="text-end">{{ number_format($group['total'], 2) }}</td>
            </tr>

            @foreach($group['children'] as $child)
                <tr>
                    <td class="child-name">{{ $child['code'] }} - {{ $child['name'] }}</td>
                    <td class="text-end">{{ number_format($child['amount'], 2) }}</td>
                </tr>
            @endforeach
        @empty
            <tr>
                <td colspan="2" class="text-center">No data</td>
            </tr>
        @endforelse
        </tbody>
    </table>
@endforeach

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