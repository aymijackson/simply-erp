<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class GeneralLedgerController extends Controller
{
    public function index()
    {
        return view('finance.general_ledgers.index');
    }

    /**
     * Select2 lookup for chart of accounts.
     * Uses finance_accounts table (company scoped).
     */
    public function accountsLookup(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;
        $q = trim((string)$request->q);

        $rows = DB::table('finance_accounts as a')
            ->where('a.company_id', $companyId)
            ->whereNull('a.deleted_at')
            ->when($q !== '', function ($x) use ($q) {
                $x->where('a.code', 'like', "%{$q}%")
                  ->orWhere('a.name', 'like', "%{$q}%");
            })
            ->orderBy('a.code')
            ->limit(30)
            ->get([
                'a.id',
                DB::raw("CONCAT(a.code,' - ',a.name) as text"),
            ]);

        return response()->json(['results' => $rows]);
    }

    /**
     * Ledger data endpoint (AJAX).
     * - posted-only journal entries
     * - filter by account + date range + search
     * Returns:
     *  - account meta
     *  - opening balance (before from-date)
     *  - rows with running balance
     *  - totals and closing
     */
    public function data(Request $request)
    {
        return response()->json($this->buildLedgerDataset($request));
    }
    
    protected function buildLedgerDataset(Request $request): array
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $data = Validator::make($request->all(), [
            'account_id'  => ['nullable', 'integer', 'exists:finance_accounts,id'],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
            'q'           => ['nullable', 'string', 'max:200'],
            'posted_only' => ['nullable', 'in:0,1'],
        ])->validate();
    
        $accountId  = !empty($data['account_id']) ? (int) $data['account_id'] : null;
        $dateFrom   = $data['date_from'] ?? null;
        $dateTo     = $data['date_to'] ?? null;
        $term       = trim((string) ($data['q'] ?? ''));
        $postedOnly = (int) ($data['posted_only'] ?? 1);
    
        if ($accountId) {
            $acc = DB::table('finance_accounts')
                ->where('company_id', $companyId)
                ->where('id', $accountId)
                ->whereNull('deleted_at')
                ->first(['id', 'code', 'name']);
    
            if (!$acc) {
                abort(404, 'Account not found.');
            }
    
            $base = DB::table('finance_journal_entry_lines as l')
                ->join('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
                ->where('e.company_id', $companyId)
                ->where('l.account_id', $accountId);
    
            if ($postedOnly === 1) {
                $base->where('e.status', 'posted');
            }
    
            if ($term !== '') {
                $base->where(function($x) use ($term){
                    $x->where('e.entry_no','like',"%{$term}%")
                      ->orWhere('e.reference','like',"%{$term}%")
                      ->orWhere('e.memo','like',"%{$term}%");
                });
            }
    
            $opening = 0.0;
            if ($dateFrom) {
                $openingRow = (clone $base)
                    ->where('e.entry_date', '<', $dateFrom)
                    ->selectRaw('COALESCE(SUM(COALESCE(l.debit,0) - COALESCE(l.credit,0)),0) as bal')
                    ->first();
                $opening = (float) ($openingRow->bal ?? 0);
            }
    
            $qRows = clone $base;
            if ($dateFrom) $qRows->where('e.entry_date', '>=', $dateFrom);
            if ($dateTo)   $qRows->where('e.entry_date', '<=', $dateTo);
    
            $rows = $qRows
                ->orderBy('e.entry_date')
                ->orderBy('e.id')
                ->orderBy('l.id')
                ->get([
                    'e.entry_date',
                    'e.entry_no',
                    'e.reference',
                    'e.memo',
                    'l.debit',
                    'l.credit',
                ]);
    
            $running = $opening;
            $out = [];
            $totDr = 0.0;
            $totCr = 0.0;
    
            foreach ($rows as $r) {
                $dr = (float) ($r->debit ?? 0);
                $cr = (float) ($r->credit ?? 0);
                $totDr += $dr;
                $totCr += $cr;
                $running += ($dr - $cr);
    
                $out[] = [
                    'entry_date' => $r->entry_date,
                    'entry_no'   => $r->entry_no,
                    'reference'  => $r->reference,
                    'memo'       => $r->memo,
                    'debit'      => round($dr, 2),
                    'credit'     => round($cr, 2),
                    'balance'    => round($running, 2),
                ];
            }
    
            return [
                'mode' => 'account',
                'filters' => compact('dateFrom', 'dateTo', 'term', 'postedOnly'),
                'account' => [
                    'id'   => (int) $acc->id,
                    'code' => $acc->code,
                    'name' => $acc->name,
                ],
                'opening_balance' => round($opening, 2),
                'totals' => [
                    'debit' => round($totDr, 2),
                    'credit'=> round($totCr, 2),
                    'closing_balance' => round($running, 2),
                ],
                'rows' => $out,
            ];
        }
    
        $base = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('e.company_id', $companyId)
            ->whereNull('a.deleted_at');
    
        if ($postedOnly === 1) {
            $base->where('e.status', 'posted');
        }
    
        if ($term !== '') {
            $base->where(function($x) use ($term){
                $x->where('e.entry_no','like',"%{$term}%")
                  ->orWhere('e.reference','like',"%{$term}%")
                  ->orWhere('e.memo','like',"%{$term}%")
                  ->orWhere('a.code','like',"%{$term}%")
                  ->orWhere('a.name','like',"%{$term}%");
            });
        }
    
        $accounts = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    
        $groups = [];
        $sumOpening = 0.0;
        $sumDr = 0.0;
        $sumCr = 0.0;
        $sumClosing = 0.0;
    
        foreach ($accounts as $acc) {
            $accBase = (clone $base)->where('l.account_id', $acc->id);
    
            $opening = 0.0;
            if ($dateFrom) {
                $openingRow = (clone $accBase)
                    ->where('e.entry_date', '<', $dateFrom)
                    ->selectRaw('COALESCE(SUM(COALESCE(l.debit,0) - COALESCE(l.credit,0)),0) as bal')
                    ->first();
                $opening = (float) ($openingRow->bal ?? 0);
            }
    
            $rowsQ = clone $accBase;
            if ($dateFrom) $rowsQ->where('e.entry_date', '>=', $dateFrom);
            if ($dateTo)   $rowsQ->where('e.entry_date', '<=', $dateTo);
    
            $rows = $rowsQ
                ->orderBy('e.entry_date')
                ->orderBy('e.id')
                ->orderBy('l.id')
                ->get([
                    'e.entry_date',
                    'e.entry_no',
                    'e.reference',
                    'e.memo',
                    'l.debit',
                    'l.credit',
                ]);
    
            if (!$rows->count() && abs($opening) < 0.0001) {
                continue;
            }
    
            $running = $opening;
            $totDr = 0.0;
            $totCr = 0.0;
            $outRows = [];
    
            foreach ($rows as $r) {
                $dr = (float) ($r->debit ?? 0);
                $cr = (float) ($r->credit ?? 0);
                $totDr += $dr;
                $totCr += $cr;
                $running += ($dr - $cr);
    
                $outRows[] = [
                    'entry_date' => $r->entry_date,
                    'entry_no'   => $r->entry_no,
                    'reference'  => $r->reference,
                    'memo'       => $r->memo,
                    'debit'      => round($dr, 2),
                    'credit'     => round($cr, 2),
                    'balance'    => round($running, 2),
                ];
            }
    
            $groups[] = [
                'account' => [
                    'id'   => (int) $acc->id,
                    'code' => $acc->code,
                    'name' => $acc->name,
                ],
                'opening_balance' => round($opening, 2),
                'totals' => [
                    'debit' => round($totDr, 2),
                    'credit' => round($totCr, 2),
                    'closing_balance' => round($running, 2),
                ],
                'rows' => $outRows,
            ];
    
            $sumOpening += $opening;
            $sumDr += $totDr;
            $sumCr += $totCr;
            $sumClosing += $running;
        }
    
        return [
            'mode' => 'general',
            'filters' => compact('dateFrom', 'dateTo', 'term', 'postedOnly'),
            'summary' => [
                'opening_balance' => round($sumOpening, 2),
                'debit'           => round($sumDr, 2),
                'credit'          => round($sumCr, 2),
                'closing_balance' => round($sumClosing, 2),
            ],
            'groups_count' => count($groups),
            'groups' => $groups,
        ];
    }
    
    public function excel(Request $request)
    {
        $report = $this->buildLedgerDataset($request);
    
        $filename = 'general_ledger_' . now()->format('Ymd_His') . '.csv';
    
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];
    
        $callback = function () use ($report) {
            $out = fopen('php://output', 'w');
    
            fputcsv($out, ['General Ledger Export']);
            fputcsv($out, []);
    
            if ($report['mode'] === 'account') {
                fputcsv($out, ['Mode', 'Account Ledger']);
                fputcsv($out, ['Account', ($report['account']['code'] ?? '') . ' - ' . ($report['account']['name'] ?? '')]);
                fputcsv($out, ['Opening', $report['opening_balance'] ?? 0]);
                fputcsv($out, ['Debit', $report['totals']['debit'] ?? 0]);
                fputcsv($out, ['Credit', $report['totals']['credit'] ?? 0]);
                fputcsv($out, ['Closing', $report['totals']['closing_balance'] ?? 0]);
                fputcsv($out, []);
    
                fputcsv($out, ['Date', 'Entry No', 'Reference', 'Memo', 'Debit', 'Credit', 'Running Balance']);
                foreach (($report['rows'] ?? []) as $row) {
                    fputcsv($out, [
                        $row['entry_date'] ?? '',
                        $row['entry_no'] ?? '',
                        $row['reference'] ?? '',
                        $row['memo'] ?? '',
                        $row['debit'] ?? 0,
                        $row['credit'] ?? 0,
                        $row['balance'] ?? 0,
                    ]);
                }
            } else {
                fputcsv($out, ['Mode', 'General Ledger']);
                fputcsv($out, ['Opening', $report['summary']['opening_balance'] ?? 0]);
                fputcsv($out, ['Debit', $report['summary']['debit'] ?? 0]);
                fputcsv($out, ['Credit', $report['summary']['credit'] ?? 0]);
                fputcsv($out, ['Closing', $report['summary']['closing_balance'] ?? 0]);
                fputcsv($out, []);
    
                foreach (($report['groups'] ?? []) as $group) {
                    fputcsv($out, ['Account', ($group['account']['code'] ?? '') . ' - ' . ($group['account']['name'] ?? '')]);
                    fputcsv($out, ['Opening', $group['opening_balance'] ?? 0]);
                    fputcsv($out, ['Debit', $group['totals']['debit'] ?? 0]);
                    fputcsv($out, ['Credit', $group['totals']['credit'] ?? 0]);
                    fputcsv($out, ['Closing', $group['totals']['closing_balance'] ?? 0]);
                    fputcsv($out, ['Date', 'Entry No', 'Reference', 'Memo', 'Debit', 'Credit', 'Running Balance']);
    
                    foreach (($group['rows'] ?? []) as $row) {
                        fputcsv($out, [
                            $row['entry_date'] ?? '',
                            $row['entry_no'] ?? '',
                            $row['reference'] ?? '',
                            $row['memo'] ?? '',
                            $row['debit'] ?? 0,
                            $row['credit'] ?? 0,
                            $row['balance'] ?? 0,
                        ]);
                    }
    
                    fputcsv($out, []);
                }
            }
    
            fclose($out);
        };
    
        return response()->stream($callback, 200, $headers);
    }
    
    public function pdf(Request $request)
    {
        $report = $this->buildLedgerDataset($request);
    
        return view('finance.general_ledgers.pdf', compact('report'));
    }
}