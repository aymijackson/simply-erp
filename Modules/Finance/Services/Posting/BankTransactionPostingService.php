<?php

namespace Modules\Finance\Services\Posting;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankTransactionPostingService
{
    public static function post(int $companyId, int $txnId): int
    {
        return DB::transaction(function () use ($companyId, $txnId) {

            $tx = DB::table('finance_bank_transactions')
                ->where('company_id', $companyId)
                ->where('id', $txnId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$tx) {
                throw ValidationException::withMessages(['txn' => ['Bank transaction not found.']]);
            }

            if ($tx->status === 'void') {
                throw ValidationException::withMessages(['txn' => ['Cannot post a VOID transaction.']]);
            }

            if ($tx->status === 'posted' && !empty($tx->journal_entry_id)) {
                return (int)$tx->journal_entry_id; // idempotent
            }

            // Period lock enforcement
            self::assertDatePostable($companyId, (string)$tx->txn_date);

            // Bank GL
            $bank = DB::table('finance_bank_accounts')
                ->where('company_id', $companyId)
                ->where('id', (int)$tx->bank_account_id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$bank) {
                throw ValidationException::withMessages(['bank_account_id' => ['Bank account not found.']]);
            }
            if (empty($bank->gl_account_id)) {
                throw ValidationException::withMessages(['gl_account_id' => ['Bank account must be linked to a GL account.']]);
            }

            $type = (string)$tx->type;

            // Transfer: needs destination bank + no splits required
            $toBank = null;
            if ($type === 'transfer') {
                if (empty($tx->to_bank_account_id)) {
                    throw ValidationException::withMessages(['to_bank_account_id' => ['Destination bank account is required for transfer.']]);
                }
                if ((int)$tx->to_bank_account_id === (int)$tx->bank_account_id) {
                    throw ValidationException::withMessages(['to_bank_account_id' => ['Transfer destination cannot be the same bank account.']]);
                }

                $toBank = DB::table('finance_bank_accounts')
                    ->where('company_id', $companyId)
                    ->where('id', (int)$tx->to_bank_account_id)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (!$toBank) {
                    throw ValidationException::withMessages(['to_bank_account_id' => ['Destination bank account not found.']]);
                }
                if (empty($toBank->gl_account_id)) {
                    throw ValidationException::withMessages(['to_bank_account_id' => ['Destination bank account must be linked to a GL account.']]);
                }
            }

            $lines = DB::table('finance_bank_transaction_lines')
                ->where('bank_transaction_id', $tx->id)
                ->orderBy('line_no')
                ->get(['account_id','memo','debit','credit']);

            // For non-transfer, we require split lines
            if ($type !== 'transfer' && $lines->count() < 1) {
                throw ValidationException::withMessages(['lines' => ['Add at least one split line before posting.']]);
            }

            // Compute totals from stored split lines
            $totalDebit  = (float)$lines->sum('debit');
            $totalCredit = (float)$lines->sum('credit');

            // Determine posting amount:
            // - deposit: splits are CREDIT lines, bank is DEBIT
            // - withdrawal: splits are DEBIT lines, bank is CREDIT
            // - transfer: amount is tx.total_amount, no splits
            $amount = 0.00;

            if ($type === 'deposit') {
                $amount = round($totalCredit, 2);
                if ($amount <= 0) {
                    throw ValidationException::withMessages(['lines' => ['Deposit requires split lines with CREDIT > 0.']]);
                }
            } elseif ($type === 'withdrawal') {
                $amount = round($totalDebit, 2);
                if ($amount <= 0) {
                    throw ValidationException::withMessages(['lines' => ['Withdrawal requires split lines with DEBIT > 0.']]);
                }
            } else { // transfer
                $amount = round((float)($tx->total_amount ?? 0), 2);
                if ($amount <= 0) {
                    throw ValidationException::withMessages(['total_amount' => ['Transfer amount must be > 0.']]);
                }
            }

            // Create JE
            $entryNo = (string)($tx->txn_no ?: ('BT-' . $tx->id . '-' . date('YmdHis')));

            $jeId = DB::table('finance_journal_entries')->insertGetId([
                'company_id' => $companyId,
                'period_id'  => self::resolvePeriodId($companyId, (string)$tx->txn_date),
                'entry_no'   => $entryNo,
                'entry_date' => $tx->txn_date,
                'memo'       => ($type === 'transfer'
                    ? 'Bank Transfer'
                    : ($type === 'deposit' ? 'Bank Deposit' : 'Bank Withdrawal')
                ) . ' - ' . ($tx->reference ?: ('TXN #' . $tx->id)),
                'status'     => 'posted',
                'posted_at'  => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $jeLines = [];

            if ($type === 'deposit') {
                // DR Bank, CR splits
                $jeLines[] = self::jl($jeId, (int)$bank->gl_account_id, $amount, 0, 'Bank increase');

                foreach ($lines as $l) {
                    if (round((float)$l->credit, 2) <= 0) continue;
                    $jeLines[] = self::jl($jeId, (int)$l->account_id, 0, (float)$l->credit, $l->memo);
                }
            } elseif ($type === 'withdrawal') {
                // CR Bank, DR splits
                $jeLines[] = self::jl($jeId, (int)$bank->gl_account_id, 0, $amount, 'Bank decrease');

                foreach ($lines as $l) {
                    if (round((float)$l->debit, 2) <= 0) continue;
                    $jeLines[] = self::jl($jeId, (int)$l->account_id, (float)$l->debit, 0, $l->memo);
                }
            } else {
                // Transfer: CR from bank, DR to bank
                $jeLines[] = self::jl($jeId, (int)$bank->gl_account_id, 0, $amount, 'Transfer out');
                $jeLines[] = self::jl($jeId, (int)$toBank->gl_account_id, $amount, 0, 'Transfer in');
            }

            // Validate JE balanced
            $sumD = round(array_sum(array_map(fn($x)=>(float)$x['debit'], $jeLines)), 2);
            $sumC = round(array_sum(array_map(fn($x)=>(float)$x['credit'], $jeLines)), 2);

            if ($sumD !== $sumC) {
                throw ValidationException::withMessages([
                    'balance' => ["Posting failed: journal not balanced (DR {$sumD} != CR {$sumC}). Check your split lines."]
                ]);
            }

            DB::table('finance_journal_entry_lines')->insert($jeLines);

            // link posting
            DB::table('finance_posting_links')->insert([
                'company_id'       => $companyId,
                'source_type'      => 'bank_txn',
                'source_id'        => $tx->id,
                'journal_entry_id' => $jeId,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // update transaction
            DB::table('finance_bank_transactions')
                ->where('id', $tx->id)
                ->update([
                    'status'          => 'posted',
                    'journal_entry_id'=> $jeId,
                    'posted_at'       => now(),
                    'posted_by'       => auth()->id(),
                    'total_amount'    => $amount, // ensure stored
                    'updated_at'      => now(),
                ]);

            return (int)$jeId;
        });
    }

    public static function unpost(int $companyId, int $txnId): void
    {
        DB::transaction(function () use ($companyId, $txnId) {

            $tx = DB::table('finance_bank_transactions')
                ->where('company_id', $companyId)
                ->where('id', $txnId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$tx) {
                throw ValidationException::withMessages(['txn' => ['Bank transaction not found.']]);
            }

            if ($tx->status !== 'posted' || empty($tx->journal_entry_id)) {
                throw ValidationException::withMessages(['txn' => ['Transaction is not posted.']]);
            }

            // Period lock enforcement
            self::assertDatePostable($companyId, (string)$tx->txn_date);

            $jeId = (int)$tx->journal_entry_id;

            // delete JE lines + header
            DB::table('finance_journal_entry_lines')->where('journal_entry_id', $jeId)->delete();
            DB::table('finance_journal_entries')->where('id', $jeId)->delete();

            // delete posting link
            DB::table('finance_posting_links')
                ->where('company_id', $companyId)
                ->where('source_type', 'bank_txn')
                ->where('source_id', $tx->id)
                ->delete();

            // update transaction
            DB::table('finance_bank_transactions')
                ->where('id', $tx->id)
                ->update([
                    'status'           => 'draft',
                    'journal_entry_id' => null,
                    'posted_at'        => null,
                    'posted_by'        => null,
                    'updated_at'       => now(),
                ]);
        });
    }

    private static function jl(int $jeId, int $accountId, float $debit, float $credit, ?string $memo): array
    {
        return [
            'journal_entry_id' => $jeId,
            'account_id'       => $accountId,
            'debit'            => round($debit, 2),
            'credit'           => round($credit, 2),
            'memo'             => $memo,
            'created_at'       => now(),
            'updated_at'       => now(),
        ];
    }

    private static function resolvePeriodId(int $companyId, string $date): ?int
    {
        $p = DB::table('finance_fiscal_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->orderBy('start_date')
            ->first();

        return $p?->id ? (int)$p->id : null;
    }

    private static function assertDatePostable(int $companyId, string $date): void
    {
        $p = DB::table('finance_fiscal_periods')
            ->where('company_id', $companyId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        if ($p && (int)($p->is_closed ?? 0) === 1) {
            throw ValidationException::withMessages(['period' => ['Posting blocked: fiscal period is closed for this date.']]);
        }

        $settings = DB::table('finance_company_settings')->where('company_id', $companyId)->first();
        $lockDate = $settings->lock_date ?? null;

        if ($lockDate && $date <= $lockDate) {
            throw ValidationException::withMessages(['lock_date' => ["Posting blocked: lock date is {$lockDate}."]]);
        }
    }
}