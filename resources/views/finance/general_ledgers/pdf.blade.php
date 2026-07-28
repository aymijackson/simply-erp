<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name') }} - General Ledger</title>
    <style>
        @page {
            margin: 110px 30px 70px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
            margin: 0;
            padding: 0;
        }

        header {
            position: fixed;
            top: -85px;
            left: 0;
            right: 0;
            height: 70px;
            border-bottom: 2px solid #1f4e78;
            padding-bottom: 8px;
        }

        footer {
            position: fixed;
            bottom: -45px;
            left: 0;
            right: 0;
            height: 30px;
            border-top: 1px solid #ccc;
            font-size: 10px;
            color: #666;
            padding-top: 6px;
        }

        .footer-left {
            float: left;
        }

        .footer-right {
            float: right;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #1f4e78;
            margin: 0 0 4px 0;
        }

        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }

        .report-meta {
            font-size: 10px;
            color: #666;
            margin-top: 4px;
        }

        .filters-box,
        .summary-box {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        .filters-box td,
        .summary-box td {
            border: 1px solid #d9d9d9;
            padding: 6px 8px;
            vertical-align: top;
        }

        .filters-box .label,
        .summary-box .label {
            background: #f3f6fa;
            font-weight: bold;
            width: 120px;
        }

        .summary-box .value {
            text-align: right;
            font-weight: bold;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1f4e78;
            border-bottom: 1px solid #1f4e78;
            padding-bottom: 4px;
            margin: 18px 0 8px 0;
        }

        .account-header {
            background: #eef4fb;
            border: 1px solid #cfdceb;
            padding: 8px 10px;
            margin: 14px 0 8px 0;
        }

        .account-name {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .account-stats {
            font-size: 10px;
            color: #333;
        }

        .account-stats span {
            margin-right: 14px;
        }

        table.ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }

        table.ledger-table th,
        table.ledger-table td {
            border: 1px solid #d9d9d9;
            padding: 6px 8px;
            vertical-align: top;
        }

        table.ledger-table thead th {
            background: #f3f6fa;
            font-weight: bold;
            text-align: left;
        }

        .text-end {
            text-align: right;
        }

        .no-data {
            text-align: center;
            color: #777;
            padding: 16px 0;
        }
    </style>
</head>
<body>
    <header>
        <div class="company-name">{{ config('app.name') }}</div>
        <div class="report-title">
            {{ ($report['mode'] ?? 'general') === 'account' ? 'Account Ledger' : 'General Ledger' }}
        </div>
        <div class="report-meta">
            Generated on {{ now()->format('d-m-Y H:i') }}
        </div>
    </header>

    <footer>
        <div class="footer-left">
            {{ config('app.name') }} • Finance Reports
        </div>
        <div class="footer-right">
            Page <span class="pagenum"></span>
        </div>
    </footer>

    @php
        $filters = $report['filters'] ?? [];
        $dateFrom = $filters['dateFrom'] ?? null;
        $dateTo = $filters['dateTo'] ?? null;
        $term = $filters['term'] ?? null;
        $postedOnly = (int)($filters['postedOnly'] ?? 1);
    @endphp

    <table class="filters-box">
        <tr>
            <td class="label">Report Mode</td>
            <td>{{ ($report['mode'] ?? 'general') === 'account' ? 'Account Ledger' : 'General Ledger' }}</td>
            <td class="label">Posted Only</td>
            <td>{{ $postedOnly === 1 ? 'Yes' : 'No' }}</td>
        </tr>
        <tr>
            <td class="label">From Date</td>
            <td>{{ $dateFrom ?: '—' }}</td>
            <td class="label">To Date</td>
            <td>{{ $dateTo ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Search</td>
            <td colspan="3">{{ ($term !== '' && $term !== null) ? $term : '—' }}</td>
        </tr>
    </table>

    @if(($report['mode'] ?? 'general') === 'account')
        <table class="summary-box">
            <tr>
                <td class="label">Account</td>
                <td colspan="3">
                    {{ ($report['account']['code'] ?? '') }}{{ !empty($report['account']['name']) ? ' - ' . $report['account']['name'] : '' }}
                </td>
            </tr>
            <tr>
                <td class="label">Opening</td>
                <td class="value">{{ number_format($report['opening_balance'] ?? 0, 2) }}</td>
                <td class="label">Closing</td>
                <td class="value">{{ number_format($report['totals']['closing_balance'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Period Debit</td>
                <td class="value">{{ number_format($report['totals']['debit'] ?? 0, 2) }}</td>
                <td class="label">Period Credit</td>
                <td class="value">{{ number_format($report['totals']['credit'] ?? 0, 2) }}</td>
            </tr>
        </table>

        <div class="section-title">Ledger Entries</div>

        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Date</th>
                    <th style="width: 120px;">Entry No</th>
                    <th style="width: 140px;">Reference</th>
                    <th>Memo</th>
                    <th style="width: 95px;" class="text-end">Debit</th>
                    <th style="width: 95px;" class="text-end">Credit</th>
                    <th style="width: 110px;" class="text-end">Running Balance</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($report['rows'] ?? []) as $row)
                    <tr>
                        <td>{{ $row['entry_date'] ?? '' }}</td>
                        <td>{{ $row['entry_no'] ?? '' }}</td>
                        <td>{{ $row['reference'] ?? '' }}</td>
                        <td>{{ $row['memo'] ?? '' }}</td>
                        <td class="text-end">{{ number_format($row['debit'] ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($row['credit'] ?? 0, 2) }}</td>
                        <td class="text-end">{{ number_format($row['balance'] ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="no-data">No ledger rows found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table class="summary-box">
            <tr>
                <td class="label">Accounts Returned</td>
                <td class="value">{{ number_format($report['groups_count'] ?? 0) }}</td>
                <td class="label">Closing Balance</td>
                <td class="value">{{ number_format($report['summary']['closing_balance'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Opening</td>
                <td class="value">{{ number_format($report['summary']['opening_balance'] ?? 0, 2) }}</td>
                <td class="label">Period Debit</td>
                <td class="value">{{ number_format($report['summary']['debit'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Period Credit</td>
                <td class="value">{{ number_format($report['summary']['credit'] ?? 0, 2) }}</td>
                <td class="label">Check</td>
                <td class="value">{{ number_format((($report['summary']['debit'] ?? 0) - ($report['summary']['credit'] ?? 0)), 2) }}</td>
            </tr>
        </table>

        <div class="section-title">Ledger by Account</div>

        @forelse(($report['groups'] ?? []) as $group)
            <div class="account-header">
                <div class="account-name">
                    {{ ($group['account']['code'] ?? '') }}{{ !empty($group['account']['name']) ? ' - ' . $group['account']['name'] : '' }}
                </div>
                <div class="account-stats">
                    <span><strong>Opening:</strong> {{ number_format($group['opening_balance'] ?? 0, 2) }}</span>
                    <span><strong>Debit:</strong> {{ number_format($group['totals']['debit'] ?? 0, 2) }}</span>
                    <span><strong>Credit:</strong> {{ number_format($group['totals']['credit'] ?? 0, 2) }}</span>
                    <span><strong>Closing:</strong> {{ number_format($group['totals']['closing_balance'] ?? 0, 2) }}</span>
                </div>
            </div>

            <table class="ledger-table">
                <thead>
                    <tr>
                        <th style="width: 80px;">Date</th>
                        <th style="width: 120px;">Entry No</th>
                        <th style="width: 140px;">Reference</th>
                        <th>Memo</th>
                        <th style="width: 95px;" class="text-end">Debit</th>
                        <th style="width: 95px;" class="text-end">Credit</th>
                        <th style="width: 110px;" class="text-end">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(($group['rows'] ?? []) as $row)
                        <tr>
                            <td>{{ $row['entry_date'] ?? '' }}</td>
                            <td>{{ $row['entry_no'] ?? '' }}</td>
                            <td>{{ $row['reference'] ?? '' }}</td>
                            <td>{{ $row['memo'] ?? '' }}</td>
                            <td class="text-end">{{ number_format($row['debit'] ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($row['credit'] ?? 0, 2) }}</td>
                            <td class="text-end">{{ number_format($row['balance'] ?? 0, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="no-data">No rows found for this account.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @empty
            <div class="no-data">No ledger entries found for the selected filters.</div>
        @endforelse
    @endif

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