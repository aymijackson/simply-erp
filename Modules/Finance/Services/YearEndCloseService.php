<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class YearEndCloseService
{
    public static function computeNetProfit(int $companyId, string $from, string $to): float
    {
        $profit = DB::table('finance_accounts as a')
            ->join('finance_account_types as t', 't.id', '=', 'a.account_type_id')
            ->leftJoin('finance_journal_entry_lines as jel', 'jel.account_id', '=', 'a.id')
            ->leftJoin('finance_journal_entries as je', function ($join) use ($from, $to) {
                $join->on('je.id', '=', 'jel.journal_entry_id')
                    ->where('je.status', 'posted')
                    ->whereBetween('je.entry_date', [$from, $to]);
            })
            ->where('a.company_id', $companyId)
            ->whereIn('t.category', ['income', 'cogs', 'expense'])
            ->selectRaw('
                SUM(
                    CASE
                        WHEN t.normal_balance = "credit"
                            THEN COALESCE(jel.credit,0) - COALESCE(jel.debit,0)
                        ELSE
                            COALESCE(jel.debit,0) - COALESCE(jel.credit,0)
                    END
                ) as net_profit
            ')
            ->value('net_profit');

        return (float)($profit ?? 0);
    }

    public static function closeYear(int $companyId, int $fiscalYearId, ?string $note = null): int
    {
        return DB::transaction(function () use ($companyId, $fiscalYearId, $note) {

            // already closed?
            $existing = DB::table('finance_year_closes')
                ->where('company_id', $companyId)
                ->where('fiscal_year_id', $fiscalYearId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'year' => ['This fiscal year has already been closed.']
                ]);
            }

            $fy = DB::table('finance_fiscal_years')
                ->where('company_id', $companyId)
                ->where('id', $fiscalYearId)
                ->lockForUpdate()
                ->first();

            if (!$fy) {
                throw ValidationException::withMessages([
                    'year' => ['Fiscal year not found.']
                ]);
            }

            if ((int)($fy->is_closed ?? 0) === 1) {
                throw ValidationException::withMessages([
                    'year' => ['This fiscal year is already marked closed.']
                ]);
            }

            $from = $fy->start_date;
            $to   = $fy->end_date;

            // Safety: no draft journals in year
            $hasDrafts = DB::table('finance_journal_entries')
                ->where('company_id', $companyId)
                ->whereBetween('entry_date', [$from, $to])
                ->where('status', '!=', 'posted')
                ->exists();

            if ($hasDrafts) {
                throw ValidationException::withMessages([
                    'year' => ['Cannot close year: there are unposted/draft journal entries within this fiscal year.']
                ]);
            }

            // Fetch settings
            $settings = DB::table('finance_company_settings')
                ->where('company_id', $companyId)
                ->first();

            $retainedId = $settings->retained_earnings_account_id ?? null;
            if (!$retainedId) {
                throw ValidationException::withMessages([
                    'retained_earnings_account_id' => ['Please set the Retained Earnings account before closing the year.']
                ]);
            }

            $incomeSummaryId = $settings->income_summary_account_id ?? null;
            if (!$incomeSummaryId) {
                throw ValidationException::withMessages([
                    'income_summary_account_id' => ['Please set an Income Summary account before closing the year (temporary equity).']
                ]);
            }

            // Compute profit
            $netProfit = self::computeNetProfit($companyId, $from, $to);

            // If zero profit, still record closure without posting a JE
            $closingJeId = null;

            if (round($netProfit, 2) != 0.0) {

                // Create a journal entry (posted)
                $entryNo = 'YE-' . $fy->id . '-' . date('YmdHis');

                $closingJeId = DB::table('finance_journal_entries')->insertGetId([
                    'company_id'   => $companyId,
                    'period_id'    => null, // optional if you store period_id; can be set to last period later
                    'entry_no'     => $entryNo,
                    'entry_date'   => $to,
                    'memo'         => 'Year-end closing entry (Retained Earnings)',
                    'status'       => 'posted',
                    'posted_at'    => now(),
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                $amount = abs($netProfit);

                // Profit: DR Income Summary, CR Retained Earnings
                // Loss:   DR Retained Earnings, CR Income Summary
                if ($netProfit > 0) {
                    DB::table('finance_journal_entry_lines')->insert([
                        [
                            'journal_entry_id' => $closingJeId,
                            'account_id'       => $incomeSummaryId,
                            'debit'            => $amount,
                            'credit'           => 0,
                            'memo'             => 'Close profit to retained earnings',
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ],
                        [
                            'journal_entry_id' => $closingJeId,
                            'account_id'       => $retainedId,
                            'debit'            => 0,
                            'credit'           => $amount,
                            'memo'             => 'Retained earnings (profit)',
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ],
                    ]);
                } else {
                    DB::table('finance_journal_entry_lines')->insert([
                        [
                            'journal_entry_id' => $closingJeId,
                            'account_id'       => $retainedId,
                            'debit'            => $amount,
                            'credit'           => 0,
                            'memo'             => 'Retained earnings (loss)',
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ],
                        [
                            'journal_entry_id' => $closingJeId,
                            'account_id'       => $incomeSummaryId,
                            'debit'            => 0,
                            'credit'           => $amount,
                            'memo'             => 'Close loss to retained earnings',
                            'created_at'       => now(),
                            'updated_at'       => now(),
                        ],
                    ]);
                }
            }

            // Record year close
            DB::table('finance_year_closes')->insert([
                'company_id'               => $companyId,
                'fiscal_year_id'           => $fiscalYearId,
                'closing_journal_entry_id' => $closingJeId,
                'net_profit'               => round($netProfit, 2),
                'closed_at'                => now(),
                'closed_by'                => auth()->id(),
                'note'                     => $note,
                'created_at'               => now(),
                'updated_at'               => now(),
            ]);

            // Mark fiscal year closed
            DB::table('finance_fiscal_years')
                ->where('id', $fiscalYearId)
                ->update([
                    'is_closed' => 1,
                    'closed_at' => now(),
                    'closed_by' => auth()->id(),
                ]);

            // Optional: Close all periods in that year (hard-lock)
            DB::table('finance_fiscal_periods')
                ->where('company_id', $companyId)
                ->whereBetween('start_date', [$from, $to])
                ->update([
                    'is_closed' => 1,
                    'closed_at' => now(),
                    'closed_by' => auth()->id(),
                ]);

            return (int)($closingJeId ?? 0);
        });
    }
}