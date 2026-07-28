<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;

class JournalPostingService
{
    public function createJournal(array $header, array $lines): int
    {
        $this->assertBalanced($lines);

        return DB::transaction(function () use ($header, $lines) {

            $entryId = DB::table('finance_journal_entries')->insertGetId([
                'company_id'   => $header['company_id'],
                'period_id'    => $header['period_id'] ?? null,
                'entry_no'     => $header['entry_no'] ?? null,
                'entry_date'   => $header['entry_date'],
                'reference'    => $header['reference'] ?? null,
                'memo'         => $header['memo'] ?? null,
                'status'       => $header['status'] ?? 'posted',
                'source_type'  => $header['source_type'] ?? null,
                'source_id'    => $header['source_id'] ?? null,
                'reversal_of_id' => $header['reversal_of_id'] ?? null,

                'posted_at'    => $header['posted_at'] ?? now(),
                'posted_by'    => $header['posted_by'] ?? null,

                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            foreach ($lines as $l) {
                DB::table('finance_journal_entry_lines')->insert([
                    'journal_entry_id' => $entryId,
                    'account_id'       => $l['account_id'],
                    'description'      => $l['description'] ?? null,
                    'debit'            => $l['debit'] ?? 0,
                    'credit'           => $l['credit'] ?? 0,
                    'memo'             => $l['memo'] ?? null,
                    'currency_code'    => $l['currency_code'] ?? null,
                    'fx_rate'          => $l['fx_rate'] ?? null,
                    'party_type'       => $l['party_type'] ?? null,
                    'party_id'         => $l['party_id'] ?? null,
                    'bank_account_id'  => $l['bank_account_id'] ?? null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }

            return (int)$entryId;
        });
    }

    public function reverseJournal(int $companyId, int $journalEntryId, array $meta): int
    {
        // meta: entry_date, reference, memo, posted_by
        return DB::transaction(function () use ($companyId, $journalEntryId, $meta) {

            $orig = DB::table('finance_journal_entries')
                ->where('company_id', $companyId)
                ->where('id', $journalEntryId)
                ->first();

            if (!$orig) {
                throw new \RuntimeException('Original journal not found.');
            }
            if ($orig->status !== 'posted') {
                throw new \RuntimeException('Only posted journals can be reversed.');
            }

            $origLines = DB::table('finance_journal_entry_lines')
                ->where('journal_entry_id', $journalEntryId)
                ->get();

            if ($origLines->count() === 0) {
                throw new \RuntimeException('Original journal has no lines.');
            }

            $revLines = [];
            foreach ($origLines as $l) {
                $revLines[] = [
                    'account_id' => $l->account_id,
                    'description' => 'REVERSAL: '.($l->description ?? ''),
                    'debit' => (float)$l->credit,
                    'credit' => (float)$l->debit,
                    'memo' => $meta['memo'] ?? null,
                    'currency_code' => $l->currency_code ?? null,
                    'fx_rate' => $l->fx_rate ?? null,
                    'party_type' => $l->party_type ?? null,
                    'party_id' => $l->party_id ?? null,
                    'bank_account_id' => $l->bank_account_id ?? null,
                ];
            }

            $revId = $this->createJournal([
                'company_id' => $companyId,
                'period_id' => $meta['period_id'] ?? $orig->period_id,
                'entry_date' => $meta['entry_date'] ?? $orig->entry_date,
                'reference'  => $meta['reference'] ?? ('REV-'.$orig->reference),
                'memo'       => $meta['memo'] ?? ('Reversal of journal #'.$orig->id),
                'status'     => 'posted',
                'source_type'=> $meta['source_type'] ?? $orig->source_type,
                'source_id'  => $meta['source_id'] ?? $orig->source_id,
                'reversal_of_id' => $orig->id,
                'posted_by'  => $meta['posted_by'] ?? null,
                'posted_at'  => now(),
            ], $revLines);

            // mark original as reversed
            DB::table('finance_journal_entries')
                ->where('company_id', $companyId)
                ->where('id', $orig->id)
                ->update([
                    'status' => 'reversed',
                    'reversed_at' => now(),
                    'reversed_by' => $meta['posted_by'] ?? null,
                    'updated_at' => now(),
                ]);

            return (int)$revId;
        });
    }

    private function assertBalanced(array $lines): void
    {
        $debit = 0; $credit = 0;
        foreach ($lines as $l) {
            $debit += (float)($l['debit'] ?? 0);
            $credit += (float)($l['credit'] ?? 0);
        }
        if (round($debit, 2) !== round($credit, 2)) {
            throw new \RuntimeException("Journal not balanced. Debit={$debit} Credit={$credit}");
        }
    }
}