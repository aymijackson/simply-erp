<?php
// File: Modules/Finance/Services/BankReconciliationService.php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Finance\Models\BankAccount;
use Modules\Finance\Models\BankReconciliation;
use Modules\Finance\Models\BankStatementLine;
use Modules\Finance\Models\BankStatementMatch;
use Modules\Finance\Models\JournalEntry;
use Modules\Finance\Models\JournalEntryLine;

class BankReconciliationService
{
    public function computeSystemBalance(int $bankAccountId, Carbon $asAtDate): float
    {
        // Net movement on bank_account_id lines up to asAtDate, posted entries only.
        $sum = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->whereNull('e.deleted_at')
            ->whereNull('l.deleted_at')
            ->where('e.status', 'posted')
            ->where('l.bank_account_id', $bankAccountId)
            ->whereDate('e.entry_date', '<=', $asAtDate->toDateString())
            ->selectRaw('COALESCE(SUM(l.debit - l.credit),0) as net')
            ->value('net');

        // Add configured opening balance (if you use opening_balance as starting point)
        $bank = BankAccount::query()->findOrFail($bankAccountId);
        $opening = (float)($bank->opening_balance ?? 0);

        // If opening_balance_date is used, you could adjust here; keep it simple and assume opening is base.
        return (float)$opening + (float)$sum;
    }

    public function computeSystemOpeningBalance(BankReconciliation $recon): float
    {
        $asAt = Carbon::parse($recon->period_start)->copy()->subDay();
        return $this->computeSystemBalance((int)$recon->bank_account_id, $asAt);
    }

    public function computeSystemClosingBalance(BankReconciliation $recon): float
    {
        $asAt = Carbon::parse($recon->period_end);
        return $this->computeSystemBalance((int)$recon->bank_account_id, $asAt);
    }

    public function suggestMatches(BankReconciliation $recon, BankStatementLine $line, int $limit = 5): array
    {
        $bankAccountId = (int)$recon->bank_account_id;

        // Candidate window with buffer
        $start = Carbon::parse($recon->period_start)->copy()->subDays(7)->toDateString();
        $end   = Carbon::parse($recon->period_end)->copy()->addDays(7)->toDateString();

        $amount = (float)$line->amount;

        // Journal line net = debit - credit should match statement amount (signed)
        $candidates = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.status', 'posted')
            ->where('l.bank_account_id', $bankAccountId)
            ->whereNull('l.cleared_at')
            ->whereNull('l.deleted_at')
            ->whereNull('e.deleted_at')
            ->whereBetween('e.entry_date', [$start, $end])
            ->select([
                'l.id as journal_entry_line_id',
                'e.entry_date',
                'e.entry_no',
                'e.reference',
                'l.description',
                'l.memo',
                'l.debit',
                'l.credit',
            ])
            ->selectRaw('(l.debit - l.credit) as net')
            ->orderByRaw('ABS((l.debit - l.credit) - ?) asc', [$amount])
            ->limit(50)
            ->get()
            ->map(function ($row) use ($line, $amount) {
                $text = strtolower(trim(($row->description ?? '').' '.($row->memo ?? '').' '.($row->reference ?? '').' '.($row->entry_no ?? '')));
                $needle = strtolower(trim(($line->description ?? '').' '.($line->reference ?? '')));

                $score = 0;
                // amount closeness
                $score += (abs(((float)$row->net) - $amount) < 0.0001) ? 60 : max(0, 40 - (int)(abs(((float)$row->net) - $amount) * 2));
                // date closeness
                $score += 20 - min(20, abs(Carbon::parse($row->entry_date)->diffInDays($line->txn_date)));
                // text overlap (very light)
                foreach (preg_split('/\s+/', $needle) as $w) {
                    if (strlen($w) >= 4 && str_contains($text, $w)) $score += 2;
                }

                $row->score = $score;
                return $row;
            })
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->toArray();

        return $candidates;
    }

    public function matchLine(BankStatementLine $line, JournalEntryLine $jel, string $method = 'manual'): void
    {
        DB::transaction(function () use ($line, $jel, $method) {
            if ($line->status === 'excluded') {
                throw new \RuntimeException('Cannot match an excluded statement line.');
            }
            if (!is_null($jel->cleared_at)) {
                throw new \RuntimeException('This journal line is already cleared.');
            }

            // Create match record
            BankStatementMatch::query()->create([
                'statement_line_id' => $line->id,
                'journal_entry_line_id' => $jel->id,
                'matched_amount' => (float)$line->amount,
                'match_method' => $method,
                'matched_by' => auth()->id(),
                'matched_at' => now(),
            ]);

            // Mark cleared on bank-side journal line
            $jel->cleared_at = now();
            $jel->cleared_by = auth()->id();
            $jel->reconciliation_id = $line->reconciliation_id;
            $jel->save();

            $line->status = 'matched';
            $line->save();
        });
    }

    public function unmatchLine(BankStatementLine $line): void
    {
        DB::transaction(function () use ($line) {
            $match = $line->match()->first();
            if (!$match) return;

            $jel = JournalEntryLine::query()->lockForUpdate()->findOrFail($match->journal_entry_line_id);

            // Undo clearing
            $jel->cleared_at = null;
            $jel->cleared_by = null;
            $jel->reconciliation_id = null;
            $jel->save();

            $match->delete();

            $line->status = 'unmatched';
            $line->save();
        });
    }

    public function excludeLine(BankStatementLine $line, ?string $reason = null): void
    {
        DB::transaction(function () use ($line, $reason) {
            // If matched, unmatch first
            if ($line->status === 'matched') {
                $this->unmatchLine($line);
            }
            $line->status = 'excluded';
            $line->exclude_reason = $reason;
            $line->save();
        });
    }

    public function createAdjustmentAndMatch(BankStatementLine $line, array $dto): void
    {
        DB::transaction(function () use ($line, $dto) {
            $recon = $line->reconciliation()->lockForUpdate()->firstOrFail();
            if (in_array($recon->status, ['closed','void'], true)) {
                throw new \RuntimeException('Reconciliation is closed/void; cannot add adjustment.');
            }

            $bankAccountId = (int)$recon->bank_account_id;
            $bankAccount = BankAccount::query()->findOrFail($bankAccountId);

            $amount = (float)$dto['amount']; // positive
            // Statement line amount is signed; adjustment should mirror statement amount direction
            $signed = (float)$line->amount; // could be + or -
            if (abs($signed) < 0.0001) {
                throw new \RuntimeException('Statement line amount is zero.');
            }

            $entry = JournalEntry::query()->create([
                'company_id' => $recon->company_id,
                'entry_no' => null,
                'entry_date' => $dto['entry_date'],
                'reference' => 'BANK-RECON',
                'memo' => $dto['memo'] ?? $line->description,
                'status' => 'posted',
                'source_type' => 'bank_reconciliation_adjustment',
                'source_id' => $line->id,
                'posted_at' => now(),
                'posted_by' => auth()->id(),
            ]);

            // bank line net should equal statement signed amount
            $bankDebit = $signed > 0 ? abs($signed) : 0;
            $bankCredit = $signed < 0 ? abs($signed) : 0;

            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => $bankAccount->gl_account_id, // GL mapping
                'description' => $dto['type'],
                'debit' => $bankDebit,
                'credit' => $bankCredit,
                'memo' => $dto['memo'] ?? $line->description,
                'currency_code' => $bankAccount->currency_code,
                'fx_rate' => 1,
                'bank_account_id' => $bankAccountId,
            ]);

            // offset line opposite
            $offsetDebit = $bankCredit; // if bank credited, offset debited
            $offsetCredit = $bankDebit; // if bank debited, offset credited

            JournalEntryLine::query()->create([
                'journal_entry_id' => $entry->id,
                'account_id' => (int)$dto['offset_account_id'],
                'description' => $dto['type'].' offset',
                'debit' => $offsetDebit,
                'credit' => $offsetCredit,
                'memo' => $dto['memo'] ?? $line->description,
                'currency_code' => $bankAccount->currency_code,
                'fx_rate' => 1,
                'bank_account_id' => null,
            ]);

            // Find the bank journal line we just created
            $bankJel = JournalEntryLine::query()
                ->where('journal_entry_id', $entry->id)
                ->where('bank_account_id', $bankAccountId)
                ->firstOrFail();

            $this->matchLine($line->fresh(), $bankJel, 'manual');
        });
    }

    public function close(BankReconciliation $recon): array
    {
        return DB::transaction(function () use ($recon) {
            $recon->refresh();
            if (in_array($recon->status, ['closed','void'], true)) {
                throw new \RuntimeException('Reconciliation already closed/void.');
            }

            // Refresh balances
            $opening = $this->computeSystemOpeningBalance($recon);
            $closing = $this->computeSystemClosingBalance($recon);

            $recon->system_opening_balance = $opening;
            $recon->system_closing_balance = $closing;

            $difference = (float)$recon->statement_closing_balance - (float)$closing;

            // Require 0 difference (tolerance 0.01)
            if (abs($difference) > 0.01) {
                return [
                    'ok' => false,
                    'difference' => $difference,
                    'message' => 'Cannot close: statement closing does not match system closing.',
                ];
            }

            $recon->status = 'closed';
            $recon->closed_by = auth()->id();
            $recon->closed_at = now();
            $recon->save();

            return [
                'ok' => true,
                'difference' => $difference,
                'message' => 'Reconciliation closed.',
            ];
        });
    }

    public function undoClose(BankReconciliation $recon): void
    {
        DB::transaction(function () use ($recon) {
            $recon->refresh();
            if ($recon->status !== 'closed') {
                throw new \RuntimeException('Only closed reconciliations can be undone.');
            }

            // Unclear all journal lines tied to this reconciliation
            JournalEntryLine::query()
                ->where('reconciliation_id', $recon->id)
                ->update([
                    'cleared_at' => null,
                    'cleared_by' => null,
                    'reconciliation_id' => null,
                ]);

            // Remove matches + reset statement lines
            $lineIds = BankStatementLine::query()->where('reconciliation_id', $recon->id)->pluck('id');

            BankStatementMatch::query()->whereIn('statement_line_id', $lineIds)->delete();

            BankStatementLine::query()
                ->where('reconciliation_id', $recon->id)
                ->where('status', 'matched')
                ->update(['status' => 'unmatched']);

            $recon->status = 'in_progress';
            $recon->closed_by = null;
            $recon->closed_at = null;
            $recon->save();
        });
    }
}