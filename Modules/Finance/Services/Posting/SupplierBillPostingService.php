<?php

namespace Modules\Finance\Services\Posting;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierBillPostingService
{
    /**
     * Post a Supplier Bill:
     * Dr expense/inventory/etc from bill lines gl_account_id
     * Cr AP Control Account
     */
    public static function post(int $companyId, int $billId): int
    {
        return DB::transaction(function () use ($companyId, $billId) {

            $bill = DB::table('finance_supplier_bills')
                ->where('company_id', $companyId)
                ->where('id', $billId)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if (!$bill) {
                throw new \RuntimeException('Bill not found.');
            }

            if (($bill->status ?? 'draft') !== 'draft') {
                throw new \RuntimeException('Only draft bills can be posted.');
            }

            $lines = DB::table('finance_supplier_bill_lines')
                ->where('bill_id', $billId)
                ->orderBy('id')
                ->get();

            if ($lines->isEmpty()) {
                throw new \RuntimeException('Bill has no lines.');
            }

            $total = (float) ($bill->total_amount ?? 0);
            if ($total <= 0) {
                throw new \RuntimeException('Bill total must be greater than 0.');
            }

            $apControlId = self::resolveApControlAccountId($companyId, $bill);

            if ($apControlId <= 0) {
                throw new \RuntimeException('AP Control Account is not configured.');
            }

            $journalHeader = [
                'company_id' => $companyId,
                'entry_date' => $bill->bill_date ?? now()->toDateString(),
                'reference'  => $bill->bill_no ?? ('BILL-' . $billId),
                'memo'       => $bill->memo ?? 'Supplier Bill Posting',
                'status'     => 'posted',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('finance_journal_entries', 'currency_code')) {
                $journalHeader['currency_code'] = $bill->currency_code ?? null;
            }

            if (Schema::hasColumn('finance_journal_entries', 'fx_rate')) {
                $journalHeader['fx_rate'] = $bill->fx_rate ?? null;
            }

            if (Schema::hasColumn('finance_journal_entries', 'exchange_rate')) {
                $journalHeader['exchange_rate'] = $bill->fx_rate ?? null;
            }

            if (Schema::hasColumn('finance_journal_entries', 'posted_at')) {
                $journalHeader['posted_at'] = now();
            }

            if (Schema::hasColumn('finance_journal_entries', 'posted_by')) {
                $journalHeader['posted_by'] = Auth::id();
            }

            if (Schema::hasColumn('finance_journal_entries', 'source_type')) {
                $journalHeader['source_type'] = 'supplier_bill';
            }

            if (Schema::hasColumn('finance_journal_entries', 'source_id')) {
                $journalHeader['source_id'] = $billId;
            }

            $jeId = DB::table('finance_journal_entries')->insertGetId($journalHeader);

            $jeLines = [];
            $drTotal = 0.0;

            foreach ($lines as $ln) {
                $glId = (int) ($ln->gl_account_id ?? 0);
                if ($glId <= 0) {
                    throw new \RuntimeException('Bill line missing GL account.');
                }

                $amt = (float) ($ln->line_total ?? 0);
                if ($amt <= 0) {
                    continue;
                }

                $drTotal += $amt;

                $row = [
                    'journal_entry_id' => $jeId,
                    'account_id'       => $glId,
                    'debit'            => round($amt, 2),
                    'credit'           => 0,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ];

                if (Schema::hasColumn('finance_journal_entry_lines', 'currency_code')) {
                    $row['currency_code'] = $bill->currency_code ?? null;
                }

                if (Schema::hasColumn('finance_journal_entry_lines', 'fx_rate')) {
                    $row['fx_rate'] = $bill->fx_rate ?? null;
                }

                if (Schema::hasColumn('finance_journal_entry_lines', 'memo')) {
                    $row['memo'] = $ln->description ?? null;
                }

                if (Schema::hasColumn('finance_journal_entry_lines', 'description')) {
                    $row['description'] = $ln->description ?? null;
                }

                $jeLines[] = $row;
            }

            if ($drTotal <= 0) {
                throw new \RuntimeException('Bill lines total must be greater than 0.');
            }

            $apRow = [
                'journal_entry_id' => $jeId,
                'account_id'       => $apControlId,
                'debit'            => 0,
                'credit'           => round($drTotal, 2),
                'created_at'       => now(),
                'updated_at'       => now(),
            ];

            if (Schema::hasColumn('finance_journal_entry_lines', 'currency_code')) {
                $apRow['currency_code'] = $bill->currency_code ?? null;
            }

            if (Schema::hasColumn('finance_journal_entry_lines', 'fx_rate')) {
                $apRow['fx_rate'] = $bill->fx_rate ?? null;
            }

            if (Schema::hasColumn('finance_journal_entry_lines', 'memo')) {
                $apRow['memo'] = 'AP Control';
            }

            if (Schema::hasColumn('finance_journal_entry_lines', 'description')) {
                $apRow['description'] = 'AP Control';
            }

            $jeLines[] = $apRow;

            DB::table('finance_journal_entry_lines')->insert($jeLines);

            DB::table('finance_supplier_bills')
                ->where('id', $billId)
                ->update([
                    'status'           => 'posted',
                    'posted_at'        => now(),
                    'posted_by'        => Auth::id(),
                    'journal_entry_id' => $jeId,
                    'balance_due'      => round($drTotal, 2),
                    'updated_at'       => now(),
                ]);

            return $jeId;
        });
    }

    protected static function resolveApControlAccountId(int $companyId, object $bill): int
    {
        if (isset($bill->payable_account_id) && (int) $bill->payable_account_id > 0) {
            return (int) $bill->payable_account_id;
        }

        if (isset($bill->ap_control_account_id) && (int) $bill->ap_control_account_id > 0) {
            return (int) $bill->ap_control_account_id;
        }

        if (Schema::hasTable('finance_account_mappings')) {
            foreach ([
                'ap_account_id',
                'accounts_payable_account_id',
                'default_ap_account_id',
                'payable_account_id',
            ] as $column) {
                if (Schema::hasColumn('finance_account_mappings', $column)) {
                    $value = DB::table('finance_account_mappings')
                        ->where('company_id', $companyId)
                        ->value($column);

                    if ((int) $value > 0) {
                        return (int) $value;
                    }
                }
            }
        }

        if (Schema::hasTable('finance_company_settings')) {
            foreach ([
                'payable_account_id',
                'ap_account_id',
                'ap_control_account_id',
                'accounts_payable_account_id',
            ] as $column) {
                if (Schema::hasColumn('finance_company_settings', $column)) {
                    $value = DB::table('finance_company_settings')
                        ->where('company_id', $companyId)
                        ->value($column);

                    if ((int) $value > 0) {
                        return (int) $value;
                    }
                }
            }
        }

        return 0;
    }
}