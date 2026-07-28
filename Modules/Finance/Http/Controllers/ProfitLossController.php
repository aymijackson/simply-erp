<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfitLossController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = $request->get('date_from', date('Y-m-01'));
        $dateTo   = $request->get('date_to', date('Y-m-t'));

        return view('finance.profit_loss.index', compact('dateFrom', 'dateTo'));
    }

    public function data(Request $request)
    {
        return response()->json($this->buildDataset($request));
    }

    public function pdf(Request $request)
    {
        $report = $this->buildDataset($request);

        $pdf = Pdf::loadView('finance.profit_loss.pdf', compact('report'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('profit_loss_' . now()->format('Ymd_His') . '.pdf');
    }

    public function excel(Request $request): StreamedResponse
    {
        $report = $this->buildDataset($request);
        $filename = 'profit_loss_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($report) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [config('app.name'), 'Profit & Loss']);
            fputcsv($out, ['Generated At', now()->format('d-m-Y H:i:s')]);
            fputcsv($out, ['From', $report['filters']['date_from']]);
            fputcsv($out, ['To', $report['filters']['date_to']]);
            fputcsv($out, ['Posted Only', $report['filters']['posted_only'] ? 'Yes' : 'No']);
            fputcsv($out, ['Comparison Mode', $report['filters']['comparison_mode_label']]);
            fputcsv($out, []);

            fputcsv($out, ['SUMMARY']);
            fputcsv($out, ['Revenue', number_format($report['meta']['income'], 2, '.', '')]);
            fputcsv($out, ['Cost of Sales', number_format($report['meta']['cogs'], 2, '.', '')]);
            fputcsv($out, ['Gross Profit', number_format($report['meta']['grossProfit'], 2, '.', '')]);
            fputcsv($out, ['Expenses', number_format($report['meta']['expenses'], 2, '.', '')]);
            fputcsv($out, ['Net Profit', number_format($report['meta']['netProfit'], 2, '.', '')]);
            fputcsv($out, ['Comparison Net Profit', number_format($report['meta']['previous_netProfit'], 2, '.', '')]);
            fputcsv($out, ['Net Profit Change', number_format($report['meta']['netProfitChange'], 2, '.', '')]);
            fputcsv($out, ['Net Profit Change %', $report['meta']['netProfitChangePctLabel']]);
            fputcsv($out, []);

            foreach (['income' => 'Income', 'cogs' => 'Cost of Sales', 'expenses' => 'Expenses'] as $key => $title) {
                fputcsv($out, [$title]);
                fputcsv($out, ['Group', 'Account Code', 'Account Name', 'Amount']);

                foreach ($report['sections'][$key] as $group) {
                    if (!empty($group['children'])) {
                        foreach ($group['children'] as $child) {
                            fputcsv($out, [
                                $group['group_code'] . ' - ' . $group['group_name'],
                                $child['code'],
                                $child['name'],
                                number_format($child['amount'], 2, '.', ''),
                            ]);
                        }
                    } else {
                        fputcsv($out, [
                            $group['group_code'] . ' - ' . $group['group_name'],
                            $group['group_code'],
                            $group['group_name'],
                            number_format($group['total'], 2, '.', ''),
                        ]);
                    }
                }

                fputcsv($out, []);
            }

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function drilldown($accountId, Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $validated = Validator::make($request->all(), [
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
            'posted_only' => ['nullable', 'in:0,1'],
        ])->validate();

        $from = $validated['date_from'] ?? date('Y-m-01');
        $to   = $validated['date_to'] ?? date('Y-m-t');
        $postedOnly = (int)($validated['posted_only'] ?? 1);

        $rows = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('j.company_id', $companyId)
            ->where('l.account_id', $accountId)
            ->whereNull('j.deleted_at')
            ->whereNull('l.deleted_at')
            ->whereBetween('j.entry_date', [$from, $to])
            ->when($postedOnly === 1, function ($q) {
                $q->where('j.status', 'posted');
            })
            ->select(
                'j.id',
                'j.entry_no',
                'j.entry_date',
                'j.reference',
                'j.memo',
                'l.debit',
                'l.credit'
            )
            ->orderBy('j.entry_date', 'desc')
            ->orderBy('j.id', 'desc')
            ->get();

        return response()->json($rows);
    }

    protected function buildDataset(Request $request): array
    {
        $companyId = auth()->user()->company_id ?? 1;

        $validated = Validator::make($request->all(), [
            'date_from'        => ['nullable', 'date'],
            'date_to'          => ['nullable', 'date'],
            'posted_only'      => ['nullable', 'in:0,1'],
            'comparison_mode'  => ['nullable', 'in:equivalent,previous_month,same_period_last_year'],
        ])->validate();

        $from = $validated['date_from'] ?? date('Y-m-01');
        $to   = $validated['date_to'] ?? date('Y-m-t');
        $postedOnly = (int)($validated['posted_only'] ?? 1);
        $comparisonMode = $validated['comparison_mode'] ?? 'equivalent';

        [$prevFrom, $prevTo, $comparisonModeLabel] = $this->getComparisonPeriod($from, $to, $comparisonMode);

        $currentRows = $this->fetchPnLRows($companyId, $from, $to, $postedOnly);
        $previousRows = $this->fetchPnLRows($companyId, $prevFrom, $prevTo, $postedOnly);

        $currentSections = $this->buildSections($currentRows);
        $previousSections = $this->buildSections($previousRows);

        $income = round(array_sum(array_column($currentSections['income'], 'total')), 2);
        $cogs   = round(array_sum(array_column($currentSections['cogs'], 'total')), 2);
        $exp    = round(array_sum(array_column($currentSections['expenses'], 'total')), 2);

        $previousIncome = round(array_sum(array_column($previousSections['income'], 'total')), 2);
        $previousCogs   = round(array_sum(array_column($previousSections['cogs'], 'total')), 2);
        $previousExp    = round(array_sum(array_column($previousSections['expenses'], 'total')), 2);

        $gross = round($income - $cogs, 2);
        $net   = round($gross - $exp, 2);

        $previousGross = round($previousIncome - $previousCogs, 2);
        $previousNet   = round($previousGross - $previousExp, 2);

        $netChange = round($net - $previousNet, 2);

        if (abs($previousNet) < 0.01) {
            $netChangePctLabel = abs($net) < 0.01 ? '0.00%' : 'N/A';
        } else {
            $netChangePctLabel = number_format(($netChange / abs($previousNet)) * 100, 2) . '%';
        }

        return [
            'filters' => [
                'date_from' => $from,
                'date_to' => $to,
                'posted_only' => $postedOnly === 1,
                'comparison_mode' => $comparisonMode,
                'comparison_mode_label' => $comparisonModeLabel,
                'current_period_label' => date('d M Y', strtotime($from)) . ' - ' . date('d M Y', strtotime($to)),
                'previous_period_label' => date('d M Y', strtotime($prevFrom)) . ' - ' . date('d M Y', strtotime($prevTo)),
            ],
            'meta' => [
                'income' => $income,
                'cogs' => $cogs,
                'expenses' => $exp,
                'grossProfit' => $gross,
                'netProfit' => $net,

                'previous_income' => $previousIncome,
                'previous_cogs' => $previousCogs,
                'previous_expenses' => $previousExp,
                'previous_grossProfit' => $previousGross,
                'previous_netProfit' => $previousNet,

                'netProfitChange' => $netChange,
                'netProfitChangePctLabel' => $netChangePctLabel,

                'current_period_label' => date('d M Y', strtotime($from)) . ' - ' . date('d M Y', strtotime($to)),
                'previous_period_label' => date('d M Y', strtotime($prevFrom)) . ' - ' . date('d M Y', strtotime($prevTo)),

                'income_lines' => $this->countLines($currentSections['income']),
                'cogs_lines' => $this->countLines($currentSections['cogs']),
                'expense_lines' => $this->countLines($currentSections['expenses']),
            ],
            'sections' => $currentSections,
        ];
    }

    protected function fetchPnLRows(int $companyId, string $from, string $to, int $postedOnly = 1)
    {
        return DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as j', 'j.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->join('finance_account_types as t', 't.id', '=', 'a.account_type_id')
            ->leftJoin('finance_accounts as p', 'p.id', '=', 'a.parent_id')
            ->where('j.company_id', $companyId)
            ->whereNull('j.deleted_at')
            ->whereNull('l.deleted_at')
            ->whereNull('a.deleted_at')
            ->whereBetween('j.entry_date', [$from, $to])
            ->whereIn('t.category', ['income', 'cogs', 'expense'])
            ->when($postedOnly === 1, function ($q) {
                $q->where('j.status', 'posted');
            })
            ->selectRaw("
                a.id,
                a.code,
                a.name,
                a.parent_id,
                p.code as parent_code,
                p.name as parent_name,
                t.category,
                ROUND(SUM(COALESCE(l.debit,0)), 2) as debit_total,
                ROUND(SUM(COALESCE(l.credit,0)), 2) as credit_total
            ")
            ->groupBy(
                'a.id',
                'a.code',
                'a.name',
                'a.parent_id',
                'p.code',
                'p.name',
                't.category'
            )
            ->orderBy('a.code')
            ->get();
    }

    protected function buildSections($rows): array
    {
        $sections = [
            'income'   => [],
            'cogs'     => [],
            'expenses' => [],
        ];

        foreach ($rows as $r) {
            $category = strtolower((string) $r->category);

            if ($category === 'income') {
                $amount = (float)$r->credit_total - (float)$r->debit_total;
                $section = 'income';
            } elseif ($category === 'cogs') {
                $amount = (float)$r->debit_total - (float)$r->credit_total;
                $section = 'cogs';
            } else {
                $amount = (float)$r->debit_total - (float)$r->credit_total;
                $section = 'expenses';
            }

            $amount = round($amount, 2);

            if (abs($amount) < 0.01) {
                continue;
            }

            $groupId   = $r->parent_id ?: $r->id;
            $groupCode = $r->parent_code ?: $r->code;
            $groupName = $r->parent_name ?: $r->name;

            if (!isset($sections[$section][$groupId])) {
                $sections[$section][$groupId] = [
                    'group_id'   => (int)$groupId,
                    'group_code' => $groupCode,
                    'group_name' => $groupName,
                    'total'      => 0,
                    'children'   => [],
                ];
            }

            $sections[$section][$groupId]['total'] += $amount;

            if ($r->parent_id) {
                $sections[$section][$groupId]['children'][] = [
                    'id'     => (int)$r->id,
                    'code'   => $r->code,
                    'name'   => $r->name,
                    'amount' => $amount,
                ];
            }
        }

        foreach ($sections as $key => $grouped) {
            foreach ($grouped as &$g) {
                $g['total'] = round($g['total'], 2);

                usort($g['children'], function ($a, $b) {
                    return strcmp((string)$a['code'], (string)$b['code']);
                });
            }
            unset($g);

            usort($grouped, function ($a, $b) {
                return strcmp((string)$a['group_code'], (string)$b['group_code']);
            });

            $sections[$key] = array_values($grouped);
        }

        return $sections;
    }

    protected function countLines(array $groups): int
    {
        $count = 0;
        foreach ($groups as $group) {
            $count += max(1, count($group['children'] ?? []));
        }
        return $count;
    }

    protected function getComparisonPeriod(string $from, string $to, string $mode): array
    {
        $fromDate = new \DateTime($from);
        $toDate = new \DateTime($to);

        switch ($mode) {
            case 'previous_month':
                $prevFrom = (clone $fromDate)->modify('first day of last month');
                $prevTo = (clone $fromDate)->modify('last day of last month');
                $label = 'Previous month';
                break;

            case 'same_period_last_year':
                $prevFrom = (clone $fromDate)->modify('-1 year');
                $prevTo = (clone $toDate)->modify('-1 year');
                $label = 'Same period last year';
                break;

            case 'equivalent':
            default:
                $days = $fromDate->diff($toDate)->days + 1;
                $prevTo = clone $fromDate;
                $prevTo->modify('-1 day');

                $prevFrom = clone $prevTo;
                $prevFrom->modify('-' . ($days - 1) . ' days');
                $label = 'Previous equivalent period';
                break;
        }

        return [
            $prevFrom->format('Y-m-d'),
            $prevTo->format('Y-m-d'),
            $label,
        ];
    }
}