<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalanceSheetController extends Controller
{
    public function index()
    {
        return view('finance.balance_sheet.index');
    }

    public function data(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $dateFrom = $request->get('date_from', date('Y-01-01'));
        $dateTo   = $request->get('date_to', date('Y-m-d'));

        /*
         |------------------------------------------------------------
         | 1. Balance Sheet accounts only: asset, liability, equity
         |------------------------------------------------------------
         */
        $rows = DB::table('finance_accounts as a')
            ->join('finance_account_types as t', 't.id', '=', 'a.account_type_id')
            ->leftJoin('finance_journal_entry_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as j', function ($join) use ($dateTo) {
                $join->on('j.id', '=', 'l.journal_entry_id')
                    ->whereNull('j.deleted_at')
                    ->where('j.status', 'posted')
                    ->whereDate('j.entry_date', '<=', $dateTo);
            })
            ->where('a.company_id', $companyId)
            ->whereNull('a.deleted_at')
            ->whereIn('t.category', ['asset', 'liability', 'equity'])
            ->groupBy(
                'a.id',
                'a.code',
                'a.name',
                't.category',
                't.normal_balance'
            )
            ->selectRaw("
                a.id,
                a.code,
                a.name,
                t.category,
                t.normal_balance,
                COALESCE(SUM(COALESCE(l.debit,0)),0) as debit_total,
                COALESCE(SUM(COALESCE(l.credit,0)),0) as credit_total
            ")
            ->orderBy('a.code')
            ->get();

        $assets = [];
        $liabilities = [];
        $equity = [];

        foreach ($rows as $r) {
            $category = strtolower((string)$r->category);
            $dr = (float)$r->debit_total;
            $cr = (float)$r->credit_total;

            // Proper accounting sign by category
            if ($category === 'asset') {
                $balance = $dr - $cr;
                $amount = round(abs($balance),2);
                $target = &$assets;
            } elseif ($category === 'liability') {
                $amount = round($cr - $dr, 2);
                $target = &$liabilities;
            } else { // equity
                $amount = round($cr - $dr, 2);
                $target = &$equity;
            }

            // Keep zero rows if you want full chart visibility; otherwise skip.
            $target[] = [
                'id'     => (int)$r->id,
                'code'   => $r->code,
                'name'   => $r->name,
                'amount' => $amount,
            ];

            unset($target);
        }

        /*
         |------------------------------------------------------------
         | 2. Current period earnings -> Equity
         |------------------------------------------------------------
         | Income/COGS/Expense are not balance-sheet accounts, but the
         | net result must appear in equity for the BS to balance.
         */
        $plRows = DB::table('finance_accounts as a')
            ->join('finance_account_types as t', 't.id', '=', 'a.account_type_id')
            ->leftJoin('finance_journal_entry_lines as l', 'l.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as j', function ($join) use ($dateFrom, $dateTo) {
                $join->on('j.id', '=', 'l.journal_entry_id')
                    ->whereNull('j.deleted_at')
                    ->where('j.status', 'posted')
                    ->whereBetween('j.entry_date', [$dateFrom, $dateTo]);
            })
            ->where('a.company_id', $companyId)
            ->whereNull('a.deleted_at')
            ->whereIn('t.category', ['income', 'cogs', 'expense'])
            ->groupBy('a.id', 't.category')
            ->selectRaw("
                a.id,
                t.category,
                COALESCE(SUM(COALESCE(l.debit,0)),0) as debit_total,
                COALESCE(SUM(COALESCE(l.credit,0)),0) as credit_total
            ")
            ->get();

        $sumIncome = 0.0;
        $sumCogs = 0.0;
        $sumExpense = 0.0;

        foreach ($plRows as $r) {
            $cat = strtolower((string)$r->category);
            $dr = (float)$r->debit_total;
            $cr = (float)$r->credit_total;

            if ($cat === 'income') {
                $sumIncome += ($cr - $dr);
            } elseif ($cat === 'cogs') {
                $sumCogs += ($dr - $cr);
            } elseif ($cat === 'expense') {
                $sumExpense += ($dr - $cr);
            }
        }

        $currentPeriodEarnings = round($sumIncome - $sumCogs - $sumExpense, 2);

        if (abs($currentPeriodEarnings) > 0.00001) {
            $equity[] = [
                'id'     => null,
                'code'   => 'SYS',
                'name'   => 'Current Period Earnings',
                'amount' => round($currentPeriodEarnings,2),
            ];
        }

        /*
         |------------------------------------------------------------
         | 3. Totals
         |------------------------------------------------------------
         */
        $sumA = round(array_sum(array_column($assets, 'amount')), 2);
        $sumL = round(array_sum(array_column($liabilities, 'amount')), 2);
        $sumE = round(array_sum(array_column($equity, 'amount')), 2);

        $check = round($sumA - ($sumL + $sumE), 2);

        return response()->json([
            'meta' => [
                'from'        => $dateFrom,
                'to'          => $dateTo,
                'assets'      => $sumA,
                'liabilities' => $sumL,
                'equity'      => $sumE,
                'check'       => $check,
                'is_balanced' => abs($check) < 0.005,
            ],
            'sections' => [
                'assets'      => $assets,
                'liabilities' => $liabilities,
                'equity'      => $equity,
            ],
        ]);
    }
}