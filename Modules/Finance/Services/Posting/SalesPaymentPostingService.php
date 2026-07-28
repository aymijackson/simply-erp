<?php

namespace Modules\Finance\Services\Posting;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesPaymentPostingService
{
    public static function post(int $companyId, int $salesPaymentId): int
    {
        return DB::transaction(function () use ($companyId, $salesPaymentId) {

            // Prevent double posting
            $existing = DB::table('finance_posting_links')
                ->where('company_id', $companyId)
                ->where('source_type', 'sales_payment')
                ->where('source_id', $salesPaymentId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return (int) $existing->journal_entry_id;
            }

            $sp = DB::table('sales_payments')
                ->where('id', $salesPaymentId)
                ->lockForUpdate()
                ->first();

            if (!$sp) {
                throw ValidationException::withMessages([
                    'payment' => ['Sales payment not found.']
                ]);
            }

            if ($sp->status !== 'posted' || empty($sp->posted_at)) {
                throw ValidationException::withMessages([
                    'status' => ['Sales payment must be posted before finance posting.']
                ]);
            }

            if (!empty($sp->voided_at)) {
                throw ValidationException::withMessages([
                    'void' => ['Cannot post a voided payment.']
                ]);
            }

            if (empty($sp->bank_account_id)) {
                throw ValidationException::withMessages([
                    'bank' => ['Bank account is required.']
                ]);
            }

            $amount = (float) $sp->amount_received;

            if (round($amount,2) <= 0) {
                throw ValidationException::withMessages([
                    'amount' => ['Payment amount must be greater than zero.']
                ]);
            }

            // Resolve Bank GL
            $bank = DB::table('finance_bank_accounts')
                ->where('company_id', $companyId)
                ->where('id', $sp->bank_account_id)
                ->whereNull('deleted_at')
                ->first();

            if (!$bank || empty($bank->gl_account_id)) {
                throw ValidationException::withMessages([
                    'bank_gl' => ['Bank account must be linked to a GL account.']
                ]);
            }

            $bankGlId = (int) $bank->gl_account_id;

            // Resolve AR GL
            $mapping = DB::table('finance_account_mappings')
                ->where('company_id', $companyId)
                ->first();

            if (!$mapping || empty($mapping->ar_account_id)) {
                throw ValidationException::withMessages([
                    'mapping' => ['AR account mapping is not configured.']
                ]);
            }

            $arGlId = (int) $mapping->ar_account_id;

            $date = $sp->payment_date;

            // Create Journal Entry
            $entryNo = 'SP-' . $salesPaymentId . '-' . date('YmdHis');

            $jeId = DB::table('finance_journal_entries')->insertGetId([
                'company_id' => $companyId,
                'entry_no'   => $entryNo,
                'entry_date' => $date,
                'memo'       => 'Sales Payment #' . ($sp->payment_no ?? $salesPaymentId),
                'status'     => 'posted',
                'posted_at'  => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // JE Lines
            DB::table('finance_journal_entry_lines')->insert([
                [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $bankGlId,
                    'debit'            => $amount,
                    'credit'           => 0,
                    'memo'             => 'Bank receipt',
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ],
                [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $arGlId,
                    'debit'            => 0,
                    'credit'           => $amount,
                    'memo'             => 'Reduce Accounts Receivable',
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ],
            ]);

            // Posting Link
            DB::table('finance_posting_links')->insert([
                'company_id'       => $companyId,
                'source_type'      => 'sales_payment',
                'source_id'        => $salesPaymentId,
                'journal_entry_id' => $jeId,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            return (int) $jeId;
        });
    }

    public static function unpost(int $companyId, int $salesPaymentId): void
    {
        DB::transaction(function () use ($companyId, $salesPaymentId) {

            $link = DB::table('finance_posting_links')
                ->where('company_id', $companyId)
                ->where('source_type', 'sales_payment')
                ->where('source_id', $salesPaymentId)
                ->lockForUpdate()
                ->first();

            if (!$link) return;

            $jeId = (int)$link->journal_entry_id;

            DB::table('finance_journal_entry_lines')
                ->where('journal_entry_id', $jeId)
                ->delete();

            DB::table('finance_journal_entries')
                ->where('id', $jeId)
                ->delete();

            DB::table('finance_posting_links')
                ->where('id', $link->id)
                ->delete();
        });
    }
}