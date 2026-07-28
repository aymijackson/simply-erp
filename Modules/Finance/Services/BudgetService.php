<?php
// File: Modules/Finance/Services/BudgetService.php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\Budget;
use Modules\Finance\Models\BudgetAmount;
use Modules\Finance\Models\BudgetLine;

class BudgetService
{
    public function periods(Budget $budget): array
    {
        $start = Carbon::parse($budget->start_date)->startOfDay();
        $end = Carbon::parse($budget->end_date)->endOfDay();

        $periods = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if ($budget->period_type === 'monthly') {
                $ps = $cursor->copy()->startOfMonth();
                $pe = $cursor->copy()->endOfMonth();
                $cursor = $cursor->copy()->addMonth();
            } elseif ($budget->period_type === 'quarterly') {
                $ps = $cursor->copy()->firstOfQuarter();
                $pe = $cursor->copy()->lastOfQuarter();
                $cursor = $cursor->copy()->addMonths(3);
            } else { // annual
                $ps = $cursor->copy()->startOfYear();
                $pe = $cursor->copy()->endOfYear();
                $cursor = $cursor->copy()->addYear();
            }

            // clip to budget range
            if ($ps->lt($start)) $ps = $start->copy();
            if ($pe->gt($end)) $pe = $end->copy();

            $key = $ps->toDateString();
            $periods[$key] = [
                'period_start' => $ps->toDateString(),
                'period_end' => $pe->toDateString(),
                'label' => $budget->period_type === 'monthly' ? $ps->format('M Y') : ($budget->period_type === 'quarterly' ? 'Q'.$ps->quarter.' '.$ps->year : (string)$ps->year),
            ];
        }

        return array_values($periods);
    }

    public function ensureLines(Budget $budget, array $accountIds): void
    {
        DB::transaction(function () use ($budget, $accountIds) {
            foreach (array_unique(array_map('intval', $accountIds)) as $aid) {
                BudgetLine::query()->firstOrCreate([
                    'budget_id' => $budget->id,
                    'account_id' => $aid,
                ]);
            }
        });
    }

    /**
     * Payload:
     * [
     *   { account_id: 123, amounts: { "2026-01-01": 1000.00, "2026-02-01": 900.00 } },
     *   ...
     * ]
     */
    public function upsertGrid(Budget $budget, array $rows): void
    {
        $periods = collect($this->periods($budget))->keyBy('period_start');

        DB::transaction(function () use ($budget, $rows, $periods) {
            foreach ($rows as $r) {
                $accountId = (int)($r['account_id'] ?? 0);
                if (!$accountId) continue;

                $line = BudgetLine::query()->firstOrCreate([
                    'budget_id' => $budget->id,
                    'account_id' => $accountId,
                ]);

                $amounts = (array)($r['amounts'] ?? []);
                foreach ($amounts as $ps => $val) {
                    if (!$periods->has($ps)) continue;
                    $p = $periods[$ps];

                    BudgetAmount::query()->updateOrCreate(
                        ['budget_line_id' => $line->id, 'period_start' => $p['period_start']],
                        ['period_end' => $p['period_end'], 'amount' => (float)$val]
                    );
                }
            }
        });
    }

    public function budgetVsActual(Budget $budget): array
    {
        $periods = $this->periods($budget);

        // Budget totals by account
        $budgetRows = DB::table('finance_budget_lines as bl')
            ->join('finance_budget_amounts as ba', 'ba.budget_line_id', '=', 'bl.id')
            ->where('bl.budget_id', $budget->id)
            ->selectRaw('bl.account_id, SUM(ba.amount) as budget_total')
            ->groupBy('bl.account_id')
            ->pluck('budget_total', 'account_id');

        // Actual totals by account from posted journals within budget range
        $actualRows = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.status', 'posted')
            ->whereNull('e.deleted_at')
            ->whereNull('l.deleted_at')
            ->whereBetween('e.entry_date', [$budget->start_date->toDateString(), $budget->end_date->toDateString()])
            ->selectRaw('l.account_id, SUM(l.debit - l.credit) as actual_net')
            ->groupBy('l.account_id')
            ->pluck('actual_net', 'account_id');

        // Output (account names pulled by you in the controller if needed)
        $accounts = collect(array_unique(array_merge($budgetRows->keys()->all(), $actualRows->keys()->all())))
            ->values();

        $out = [];
        foreach ($accounts as $aid) {
            $budgetTotal = (float)($budgetRows[$aid] ?? 0);
            $actualNet = (float)($actualRows[$aid] ?? 0);

            // For UX: treat expense actual as positive if it’s a debit-heavy account.
            // You can improve this using COA normal balance; this is a baseline.
            $actual = $actualNet;

            $variance = $budgetTotal - $actual;
            $varPct = ($budgetTotal != 0) ? ($variance / $budgetTotal) * 100 : null;

            $out[] = [
                'account_id' => (int)$aid,
                'budget' => $budgetTotal,
                'actual' => $actual,
                'variance' => $variance,
                'variance_pct' => $varPct,
            ];
        }

        return ['periods' => $periods, 'rows' => $out];
    }
}