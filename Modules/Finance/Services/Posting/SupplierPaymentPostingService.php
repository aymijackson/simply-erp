<?php

namespace Modules\Finance\Services\Posting;

use Illuminate\Support\Facades\DB;

class SupplierPaymentPostingService
{
    /**
     * Post a Supplier Payment:
     * - Create JE:
     *      Dr AP Control
     *      Cr Bank/Cash GL (from bank_account.gl_account_id)
     * - Reduce bill balances (allocation lines)
     * - Mark payment posted
     */
    public static function post(int $companyId, int $paymentId): int
    {
        return DB::transaction(function () use ($companyId, $paymentId) {

            $pmt = DB::table('finance_supplier_payments')
                ->where('company_id', $companyId)
                ->where('id', $paymentId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$pmt) throw new \RuntimeException('Payment not found.');
            if (($pmt->status ?? 'draft') !== 'draft') throw new \RuntimeException('Only draft payments can be posted.');

            $lines = DB::table('finance_supplier_payment_lines')
                ->where('payment_id', $paymentId)
                ->get();

            if ($lines->isEmpty()) throw new \RuntimeException('Payment has no allocations.');

            $total = (float)($pmt->total_amount ?? 0);
            if ($total <= 0) throw new \RuntimeException('Payment total must be > 0.');

            // AP control: override or company default
            $apControlId = (int)($pmt->ap_control_account_id ?? 0);
            if ($apControlId <= 0) {
                $apControlId = (int) DB::table('finance_company_settings')
                    ->where('company_id', $companyId)
                    ->value('ap_control_account_id');
            }
            if ($apControlId <= 0) throw new \RuntimeException('AP Control Account is not configured.');

            // Bank GL account
            $bankId = (int)($pmt->bank_account_id ?? 0);
            if ($bankId <= 0) throw new \RuntimeException('Bank account is required.');

            $bank = DB::table('finance_bank_accounts')
                ->where('company_id', $companyId)
                ->where('id', $bankId)
                ->first();

            if (!$bank) throw new \RuntimeException('Bank account not found.');

            // IMPORTANT: adjust if your column name differs
            $bankGlId = (int)($bank->gl_account_id ?? 0);
            if ($bankGlId <= 0) throw new \RuntimeException('Bank account has no linked GL account.');

            // Validate allocations + apply to bills (lock bills)
            $allocTotal = 0.0;

            foreach ($lines as $ln) {
                $amt = round((float)$ln->amount_applied, 2);
                if ($amt <= 0) continue;

                $billId = (int)$ln->bill_id;

                $bill = DB::table('finance_supplier_bills')
                    ->where('company_id', $companyId)
                    ->where('supplier_id', (int)$pmt->supplier_id)
                    ->where('id', $billId)
                    ->whereNull('deleted_at')
                    ->lockForUpdate()
                    ->first();

                if (!$bill) {
                    throw new \RuntimeException("Invalid bill allocation: Bill #{$billId} not found for supplier.");
                }
                if (($bill->status ?? '') !== 'posted') {
                    throw new \RuntimeException("Bill {$bill->bill_no} is not posted.");
                }
                if ((float)$bill->balance_due <= 0) {
                    throw new \RuntimeException("Bill {$bill->bill_no} has no balance due.");
                }
                if ($amt > (float)$bill->balance_due + 0.005) {
                    throw new \RuntimeException("Allocation exceeds balance due for bill {$bill->bill_no}.");
                }

                // Apply allocation to bill balance
                DB::table('finance_supplier_bills')->where('id', $billId)->update([
                    'balance_due' => round(((float)$bill->balance_due - $amt), 2),
                    'updated_at'  => now(),
                ]);

                $allocTotal += $amt;
            }

            $allocTotal = round($allocTotal, 2);
            if (abs($allocTotal - round($total, 2)) > 0.01) {
                throw new \RuntimeException('Payment total must equal sum of allocations.');
            }

            // Create JE
            $jeId = DB::table('finance_journal_entries')->insertGetId([
                'company_id'  => $companyId,
                'entry_date'  => $pmt->payment_date ?? now()->toDateString(),
                'reference'   => $pmt->payment_no ?? ('PAY-' . $paymentId),
                'memo'        => $pmt->memo ?? 'Supplier Payment Posting',
                'status'      => 'posted',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            DB::table('finance_journal_entry_lines')->insert([
                // Dr AP
                [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $apControlId,
                    'debit'            => $allocTotal,
                    'credit'           => 0,
                    'currency_code'    => $pmt->currency_code ?? null,
                    'fx_rate'          => $pmt->fx_rate ?? null,
                    'memo'             => 'AP Payment',
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ],
                // Cr Bank
                [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $bankGlId,
                    'debit'            => 0,
                    'credit'           => $allocTotal,
                    'currency_code'    => $pmt->currency_code ?? null,
                    'fx_rate'          => $pmt->fx_rate ?? null,
                    'memo'             => 'Cash/Bank',
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ],
            ]);

            // Mark payment posted
            DB::table('finance_supplier_payments')->where('id', $paymentId)->update([
                'status'           => 'posted',
                'posted_at'        => now(),
                'posted_by'        => auth()->id(),
                'journal_entry_id' => $jeId,
                'updated_at'       => now(),
            ]);

            return $jeId;
        });
    }
}