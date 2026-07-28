<?php

namespace Modules\Finance\Services\Posting;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierCreditPostingService
{
    public static function post(int $companyId, int $creditId): int
    {
        return DB::transaction(function () use ($companyId, $creditId) {
            $credit = DB::table('finance_supplier_credits')
                ->where('company_id', $companyId)
                ->where('id', $creditId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$credit) {
                throw new \RuntimeException('Supplier credit not found.');
            }

            if (($credit->status ?? 'draft') !== 'draft') {
                throw new \RuntimeException('Only draft supplier credits can be posted.');
            }

            $distLines = DB::table('finance_supplier_credit_lines')
                ->where('supplier_credit_id', $creditId)
                ->orderBy('id')
                ->get();

            if ($distLines->isEmpty()) {
                throw new \RuntimeException('Supplier credit has no distribution lines.');
            }

            $applications = DB::table('finance_supplier_credit_applications')
                ->where('supplier_credit_id', $creditId)
                ->orderBy('id')
                ->get();

            $totalAmount = (float) ($credit->total_amount ?? 0);
            if ($totalAmount <= 0) {
                throw new \RuntimeException('Supplier credit total must be greater than 0.');
            }

            $apAccountId = self::resolveApControlAccountId($companyId, $credit);
            if ($apAccountId <= 0) {
                throw new \RuntimeException('AP Control Account is not configured for this supplier credit.');
            }

            $entryNo = self::generateEntryNo($companyId);

            $jeData = [
                'company_id' => $companyId,
                'entry_no'   => $entryNo,
                'entry_date' => $credit->credit_date ?? now()->toDateString(),
                'reference'  => $credit->credit_no ?? ('CR-' . $creditId),
                'memo'       => $credit->memo ?? 'Supplier Credit Posting',
                'status'     => 'posted',
                'source_type'=> 'supplier_credit',
                'source_id'  => $creditId,
                'posted_at'  => now(),
                'posted_by'  => Auth::id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $journalEntryId = DB::table('finance_journal_entries')->insertGetId($jeData);

            $journalLines = [];
            $creditTotal = 0.0;

            foreach ($distLines as $line) {
                $accountId = (int) ($line->gl_account_id ?? 0);
                if ($accountId <= 0) {
                    throw new \RuntimeException('One or more credit lines are missing GL account.');
                }

                $lineTotal = (float) ($line->line_total ?? 0);
                if ($lineTotal <= 0) {
                    continue;
                }

                $creditTotal += $lineTotal;

                $journalLines[] = [
                    'journal_entry_id' => $journalEntryId,
                    'account_id'       => $accountId,
                    'description'      => $line->description ?? 'Supplier credit distribution',
                    'debit'            => 0,
                    'credit'           => round($lineTotal, 2),
                    'memo'             => $line->memo ?? $credit->memo,
                    'currency_code'    => $credit->currency_code ?? null,
                    'fx_rate'          => $credit->fx_rate ?? null,
                    'party_type'       => 'supplier',
                    'party_id'         => $credit->supplier_id,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];
            }

            if ($creditTotal <= 0) {
                throw new \RuntimeException('Supplier credit lines total must be greater than 0.');
            }

            $journalLines[] = [
                'journal_entry_id' => $journalEntryId,
                'account_id'       => $apAccountId,
                'description'      => 'Supplier credit AP control',
                'debit'            => round($creditTotal, 2),
                'credit'           => 0,
                'memo'             => $credit->memo ?? 'Supplier credit AP control',
                'currency_code'    => $credit->currency_code ?? null,
                'fx_rate'          => $credit->fx_rate ?? null,
                'party_type'       => 'supplier',
                'party_id'         => $credit->supplier_id,
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            DB::table('finance_journal_entry_lines')->insert($journalLines);

            $appliedTotal = 0.0;

            foreach ($applications as $app) {
                $billId = (int) ($app->bill_id ?? 0);
                $amountApplied = (float) ($app->amount_applied ?? 0);

                if ($billId <= 0 || $amountApplied <= 0) {
                    continue;
                }

                $bill = DB::table('finance_supplier_bills')
                    ->where('company_id', $companyId)
                    ->where('id', $billId)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (!$bill) {
                    throw new \RuntimeException("Supplier bill #{$billId} not found.");
                }

                if ((int) $bill->supplier_id !== (int) $credit->supplier_id) {
                    throw new \RuntimeException("Supplier bill #{$billId} does not belong to the selected supplier.");
                }

                if (!in_array($bill->status, ['posted', 'part_paid'], true)) {
                    throw new \RuntimeException("Supplier bill #{$billId} is not open for credit application.");
                }

                $currentBalance = (float) ($bill->balance_due ?? 0);
                if ($amountApplied > $currentBalance + 0.0001) {
                    throw new \RuntimeException("Applied amount cannot exceed balance due for bill #{$billId}.");
                }

                $newAmountPaid = (float) ($bill->amount_paid ?? 0) + $amountApplied;
                $newBalance = $currentBalance - $amountApplied;

                $newStatus = 'posted';
                if ($newBalance <= 0.0001) {
                    $newBalance = 0;
                    $newStatus = 'paid';
                } elseif ($newAmountPaid > 0) {
                    $newStatus = 'part_paid';
                }

                DB::table('finance_supplier_bills')
                    ->where('id', $billId)
                    ->update([
                        'amount_paid' => round($newAmountPaid, 2),
                        'balance_due' => round($newBalance, 2),
                        'status'      => $newStatus,
                        'updated_at'  => now(),
                    ]);

                $appliedTotal += $amountApplied;
            }

            $unappliedAmount = max(0, $creditTotal - $appliedTotal);

            DB::table('finance_supplier_credits')
                ->where('id', $creditId)
                ->update([
                    'status'           => 'posted',
                    'posted_at'        => now(),
                    'posted_by'        => Auth::id(),
                    'journal_entry_id' => $journalEntryId,
                    'unapplied_amount' => round($unappliedAmount, 2),
                    'updated_at'       => now(),
                ]);

            return $journalEntryId;
        });
    }

    protected static function resolveApControlAccountId(int $companyId, object $credit): int
    {
        if (!empty($credit->ap_control_account_id)) {
            return (int) $credit->ap_control_account_id;
        }

        if (Schema::hasTable('finance_account_mappings') && Schema::hasColumn('finance_account_mappings', 'ap_account_id')) {
            $mapped = DB::table('finance_account_mappings')
                ->where('company_id', $companyId)
                ->value('ap_account_id');

            if ((int) $mapped > 0) {
                return (int) $mapped;
            }
        }

        return 0;
    }

    protected static function generateEntryNo(int $companyId): string
    {
        $count = DB::table('finance_journal_entries')
            ->where('company_id', $companyId)
            ->count() + 1;

        return 'JE-' . now()->format('Ymd') . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}