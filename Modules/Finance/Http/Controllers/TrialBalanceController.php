<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalanceController extends Controller
{
    public function index()
    {
        return view('finance.trial_balance.index');
    }

    public function datatable(Request $request)
    {
        $payload = $this->buildTrialBalance($request);

        $start = (int) ($request->input('start', 0));
        $length = (int) ($request->input('length', 25));
        $draw = (int) ($request->input('draw', 1));

        $rows = collect($payload['rows']);

        $searchValue = trim((string) $request->input('search.value', ''));
        if ($searchValue !== '') {
            $rows = $rows->filter(function ($row) use ($searchValue) {
                return stripos($row['account_raw'], $searchValue) !== false;
            })->values();
        }

        $recordsTotal = count($payload['rows']);
        $recordsFiltered = $rows->count();

        $pageRows = $rows->slice($start, $length)->values()->map(function ($row) {
            return [
                'account' => e($row['account']),
                'debit'   => number_format((float) $row['debit_raw'], 2),
                'credit'  => number_format((float) $row['credit_raw'], 2),
                'net'     => number_format((float) $row['net_raw'], 2),
            ];
        });

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $pageRows,
            'meta' => [
                'sum_debit' => round((float) $payload['summary']['sum_debit'], 2),
                'sum_credit' => round((float) $payload['summary']['sum_credit'], 2),
                'diff' => round((float) $payload['summary']['diff'], 2),
                'balanced' => (bool) $payload['summary']['balanced'],
            ],
        ]);
    }

    public function pdf(Request $request)
    {
        $report = $this->buildTrialBalance($request);

        $pdf = Pdf::loadView('finance.trial_balance.pdf', compact('report'))
            ->setPaper('a4', 'landscape');

        return $pdf->stream('trial_balance_' . now()->format('Ymd_His') . '.pdf');
    }

    public function excel(Request $request): StreamedResponse
    {
        $report = $this->buildTrialBalance($request);
        $filename = 'trial_balance_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($report) {
            $out = fopen('php://output', 'w');

            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($out, [config('app.name'), 'Trial Balance']);
            fputcsv($out, ['Generated At', now()->format('d-m-Y H:i:s')]);
            fputcsv($out, ['Status', $report['filters']['status_label']]);
            fputcsv($out, ['From Date', $report['filters']['date_from'] ?: '']);
            fputcsv($out, ['To Date', $report['filters']['date_to'] ?: '']);
            fputcsv($out, ['Non-zero only', $report['filters']['nonzero'] ? 'Yes' : 'No']);
            fputcsv($out, ['Search', $report['filters']['q'] ?: '']);
            fputcsv($out, []);

            fputcsv($out, ['Account', 'Debit', 'Credit', 'Net']);

            foreach ($report['rows'] as $row) {
                fputcsv($out, [
                    $row['account'],
                    number_format((float) $row['debit_raw'], 2, '.', ''),
                    number_format((float) $row['credit_raw'], 2, '.', ''),
                    number_format((float) $row['net_raw'], 2, '.', ''),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, [
                'TOTAL',
                number_format((float) $report['summary']['sum_debit'], 2, '.', ''),
                number_format((float) $report['summary']['sum_credit'], 2, '.', ''),
                number_format((float) $report['summary']['diff'], 2, '.', ''),
            ]);

            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function accounts(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $term = trim((string) $request->input('q', ''));

        $rows = DB::table('finance_accounts as a')
            ->where('a.company_id', $companyId)
            ->whereNull('a.deleted_at')
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('a.code', 'like', "%{$term}%")
                        ->orWhere('a.name', 'like', "%{$term}%");
                });
            })
            ->orderBy('a.code')
            ->limit(50)
            ->get([
                'a.id',
                DB::raw("CONCAT(a.code, ' - ', a.name) as text"),
            ]);

        return response()->json(['results' => $rows]);
    }

    protected function buildTrialBalance(Request $request): array
    {
        $companyId = auth()->user()->company_id ?? 1;

        $filters = Validator::make($request->all(), [
            'status' => ['nullable', 'string', 'max:50'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'nonzero' => ['nullable', 'in:0,1'],
            'q' => ['nullable', 'string', 'max:200'],
        ])->validate();

        $status = (string) ($filters['status'] ?? 'posted');
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $nonzero = (int) ($filters['nonzero'] ?? 1);
        $term = trim((string) ($filters['q'] ?? ''));

        $query = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('je.company_id', $companyId)
            ->whereNull('je.deleted_at')
            ->whereNull('l.deleted_at')
            ->whereNull('a.deleted_at')
            ->when($status !== '', function ($q) use ($status) {
                $q->where('je.status', $status);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                $q->whereDate('je.entry_date', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                $q->whereDate('je.entry_date', '<=', $dateTo);
            })
            ->when($term !== '', function ($q) use ($term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('a.code', 'like', "%{$term}%")
                        ->orWhere('a.name', 'like', "%{$term}%")
                        ->orWhere(DB::raw("CONCAT(a.code, ' - ', a.name)"), 'like', "%{$term}%");
                });
            })
            ->groupBy('a.id', 'a.code', 'a.name')
            ->select([
                'a.id as account_id',
                'a.code as account_code',
                'a.name as account_name',
                DB::raw('ROUND(SUM(COALESCE(l.debit,0)), 2) as total_debit'),
                DB::raw('ROUND(SUM(COALESCE(l.credit,0)), 2) as total_credit'),
                DB::raw('ROUND(SUM(COALESCE(l.debit,0)) - SUM(COALESCE(l.credit,0)), 2) as net'),
            ]);

        if ($nonzero === 1) {
            $query->havingRaw('ABS(ROUND(SUM(COALESCE(l.debit,0)) - SUM(COALESCE(l.credit,0)), 2)) > 0.0001');
        }

        $rows = $query
            ->orderBy('a.code')
            ->get()
            ->map(function ($row) {
                $account = trim(($row->account_code ?? '') . ' - ' . ($row->account_name ?? ''));

                return [
                    'account_id' => (int) $row->account_id,
                    'account' => $account,
                    'account_raw' => $account,
                    'debit_raw' => (float) $row->total_debit,
                    'credit_raw' => (float) $row->total_credit,
                    'net_raw' => (float) $row->net,
                ];
            })
            ->values()
            ->all();

        $sumDebit = round(array_sum(array_column($rows, 'debit_raw')), 2);
        $sumCredit = round(array_sum(array_column($rows, 'credit_raw')), 2);
        $diff = round($sumDebit - $sumCredit, 2);

        return [
            'filters' => [
                'status' => $status,
                'status_label' => $status === '' ? 'All' : ucfirst($status),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'nonzero' => $nonzero === 1,
                'q' => $term,
            ],
            'rows' => $rows,
            'summary' => [
                'sum_debit' => $sumDebit,
                'sum_credit' => $sumCredit,
                'diff' => $diff,
                'balanced' => abs($diff) <= 0.01,
            ],
        ];
    }
}