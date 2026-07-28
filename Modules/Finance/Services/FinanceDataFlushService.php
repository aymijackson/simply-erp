<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceDataFlushService
{
    /**
     * Finance setup/master tables.
     * These are NOT flushed unless include_setup = true.
     */
    protected array $setupTables = [
        'finance_account_mappings',
        'finance_accounts',
        'finance_account_types',
        'finance_bank_accounts',
        'finance_fixed_asset_categories',
        'finance_expense_categories',
        'finance_company_settings',
        'finance_fiscal_periods',
        'finance_tax_settings',
        'finance_tax_codes',
        'finance_tax_rates',
    ];

    public function preview(int $companyId, array $options): array
    {
        $summary = [
            'tables' => [],
            'total_rows' => 0,
            'include_setup' => !empty($options['include_setup']),
        ];

        foreach ($this->resolveDeletePlan($options) as $step) {
            $table = $step['table'];

            if (!Schema::hasTable($table)) {
                continue;
            }

            $count = $this->countRows(
                table: $table,
                companyId: $companyId,
                companyColumn: $step['company_column'] ?? 'company_id',
                whereInColumn: $step['where_in_column'] ?? null,
                whereInSourceTable: $step['where_in_source_table'] ?? null,
                whereInSourceCompanyColumn: $step['where_in_source_company_column'] ?? 'company_id',
            );

            $summary['tables'][] = [
                'table' => $table,
                'rows'  => $count,
            ];

            $summary['total_rows'] += $count;
        }

        return $summary;
    }

    public function run(int $companyId, array $options, int $actorId): array
    {
        return DB::transaction(function () use ($companyId, $options, $actorId) {
            $deleted = [];

            foreach ($this->resolveDeletePlan($options) as $step) {
                $table = $step['table'];

                if (!Schema::hasTable($table)) {
                    continue;
                }

                $deleted[$table] = $this->deleteRows(
                    table: $table,
                    companyId: $companyId,
                    companyColumn: $step['company_column'] ?? 'company_id',
                    whereInColumn: $step['where_in_column'] ?? null,
                    whereInSourceTable: $step['where_in_source_table'] ?? null,
                    whereInSourceCompanyColumn: $step['where_in_source_company_column'] ?? 'company_id',
                );
            }

            if (!empty($options['reset_opening_balances']) && Schema::hasTable('finance_bank_accounts')) {
                DB::table('finance_bank_accounts')
                    ->where('company_id', $companyId)
                    ->update([
                        'opening_balance' => 0,
                        'opening_balance_date' => null,
                        'updated_at' => now(),
                    ]);
            }

            if (!empty($options['reset_period_statuses']) && Schema::hasTable('finance_fiscal_periods')) {
                DB::table('finance_fiscal_periods')
                    ->where('company_id', $companyId)
                    ->update([
                        'is_closed' => false,
                        'updated_at' => now(),
                    ]);
            }

            if (!empty($options['reset_document_numbers'])) {
                $this->resetDocumentNumbers($companyId);
            }

            $this->writeAuditLog($companyId, $actorId, $options, $deleted);

            return [
                'deleted' => $deleted,
                'deleted_total' => array_sum($deleted),
            ];
        });
    }

    protected function resolveDeletePlan(array $options): array
    {
        $plan = [];

        /**
         * BANKING
         */
        if (!empty($options['include_banking']) || !empty($options['include_bank_reconciliation'])) {
            $plan = array_merge($plan, [
                ['table' => 'finance_bank_statement_matches'],
                ['table' => 'finance_bank_statement_lines'],
                ['table' => 'finance_bank_reconciliations'],
                ['table' => 'finance_bank_transaction_lines'],
                ['table' => 'finance_bank_transactions'],
            ]);
        }

        /**
         * BUDGETS
         */
        if (!empty($options['include_budgets'])) {
            $plan = array_merge($plan, [
                ['table' => 'finance_budget_amounts'],
                ['table' => 'finance_budget_lines'],
                ['table' => 'finance_budgets'],
            ]);
        }

        /**
         * AR / AP
         * Delete child/allocation/application tables first, then headers.
         */
        if (!empty($options['include_ar_ap']) || !empty($options['include_receivables']) || !empty($options['include_payables'])) {

            /**
             * RECEIVABLES
             */
            if (!empty($options['include_ar_ap']) || !empty($options['include_receivables'])) {
                $plan = array_merge($plan, [
                    ['table' => 'finance_customer_credit_applications'],
                    ['table' => 'finance_customer_payment_allocations'],
                    ['table' => 'finance_payment_allocations'],
                    ['table' => 'finance_invoice_tax_lines'],

                    ['table' => 'finance_customer_payments', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                    ['table' => 'finance_customer_credits', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                    ['table' => 'finance_payments'],

                    ['table' => 'finance_invoice_lines'],
                    ['table' => 'finance_invoices'],

                    ['table' => 'finance_customer_statements'],
                ]);
            }

            /**
             * PAYABLES
             */
            if (!empty($options['include_ar_ap']) || !empty($options['include_payables'])) {
                $plan = array_merge($plan, [
                    ['table' => 'finance_supplier_credit_applications'],
                    ['table' => 'finance_supplier_payment_allocations'],
                    ['table' => 'finance_supplier_bill_tax_lines'],
                    ['table' => 'finance_supplier_bill_payment_lines'],

                    ['table' => 'finance_supplier_bill_payments'],
                    ['table' => 'finance_supplier_payments', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                    ['table' => 'finance_supplier_credits', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],

                    ['table' => 'finance_supplier_bill_lines'],
                    ['table' => 'finance_supplier_bills'],

                    ['table' => 'finance_supplier_statements'],
                ]);
            }
        }

        /**
         * EXPENSES
         */
        if (!empty($options['include_expenses'])) {
            $plan = array_merge($plan, [
                ['table' => 'finance_expense_lines'],
                ['table' => 'finance_expenses'],
            ]);
        }

        /**
         * FIXED ASSETS
         * Use company-scoped delete for depr lines because the exact JE FK column is not confirmed.
         */
        if (!empty($options['include_fixed_assets'])) {
            $plan = array_merge($plan, [
                ['table' => 'finance_fixed_asset_depr_lines'],
                ['table' => 'finance_fixed_asset_depr_runs'],
                ['table' => 'finance_fixed_asset_transactions'],
                ['table' => 'finance_fixed_asset_revaluations'],
                ['table' => 'finance_fixed_asset_impairments'],
                ['table' => 'finance_fixed_asset_writeoffs'],
                ['table' => 'finance_fixed_asset_maintenances'],
                ['table' => 'finance_fixed_asset_transfers'],
                ['table' => 'finance_asset_capitalisations'],
                ['table' => 'finance_fixed_asset_components'],
                ['table' => 'finance_fixed_asset_service_plans'],
            ]);
        }

        /**
         * PETTY CASH
         */
        if (!empty($options['include_petty_cash'])) {
            $plan = array_merge($plan, [
                ['table' => 'petty_cash_reconciliation_lines'],
                ['table' => 'petty_cash_reconciliations'],
                ['table' => 'petty_cash_transactions'],
            ]);
        }

        if (!empty($options['include_reconciliations'])) {
            $plan = array_merge($plan, [
                ['table' => 'petty_cash_reconciliation_lines'],
                ['table' => 'petty_cash_reconciliations'],
                ['table' => 'finance_bank_reconciliations'],
            ]);
        }

        /**
         * TAX
         */
        if (!empty($options['include_tax'])) {
            $plan = array_merge($plan, [
                ['table' => 'finance_tax_transactions'],
                ['table' => 'finance_tax_filing_lines'],
                ['table' => 'finance_tax_filings'],
            ]);
        }

        /**
         * PAYROLL-RELATED FINANCE
         */
        if (!empty($options['include_payroll'])) {
            $plan = array_merge($plan, [
                ['table' => 'finance_payroll_journal_lines'],
                ['table' => 'finance_payroll_journals'],
            ]);
        }

        /**
         * JOURNAL-LINKED CHILD TABLES FIRST
         */
        if (!empty($options['include_transactions']) || !empty($options['include_journals'])) {
            $plan = array_merge($plan, [
                ['table' => 'finance_supplier_credits', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                ['table' => 'finance_supplier_payments', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                ['table' => 'finance_customer_credits', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                ['table' => 'finance_customer_payments', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                ['table' => 'finance_fixed_asset_depr_lines'],
                ['table' => 'finance_tax_transactions', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                ['table' => 'finance_bank_transactions', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                ['table' => 'finance_expenses', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                ['table' => 'petty_cash_transactions', 'where_in_column' => 'finance_journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                ['table' => 'finance_journal_entry_lines', 'where_in_column' => 'journal_entry_id', 'where_in_source_table' => 'finance_journal_entries'],
                ['table' => 'finance_journal_entries'],
            ]);
        }

        /**
         * SETUP / MASTERS LAST
         */
        if (!empty($options['include_setup'])) {
            foreach ($this->setupTables as $table) {
                $plan[] = ['table' => $table];
            }
        }

        return $this->uniquePlan($plan);
    }

    protected function uniquePlan(array $plan): array
    {
        $seen = [];
        $unique = [];

        foreach ($plan as $step) {
            $key = md5(json_encode($step));
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $step;
            }
        }

        return $unique;
    }

    protected function countRows(
        string $table,
        int $companyId,
        string $companyColumn = 'company_id',
        ?string $whereInColumn = null,
        ?string $whereInSourceTable = null,
        string $whereInSourceCompanyColumn = 'company_id'
    ): int {
        $query = DB::table($table);

        if ($whereInColumn && $whereInSourceTable && Schema::hasTable($whereInSourceTable)) {
            $ids = DB::table($whereInSourceTable)
                ->when(
                    Schema::hasColumn($whereInSourceTable, $whereInSourceCompanyColumn),
                    fn ($q) => $q->where($whereInSourceCompanyColumn, $companyId)
                )
                ->pluck('id');

            if ($ids->isEmpty()) {
                return 0;
            }

            $query->whereIn($whereInColumn, $ids);
            return $query->count();
        }

        if (Schema::hasColumn($table, $companyColumn)) {
            $query->where($companyColumn, $companyId);
        }

        return $query->count();
    }

    protected function deleteRows(
        string $table,
        int $companyId,
        string $companyColumn = 'company_id',
        ?string $whereInColumn = null,
        ?string $whereInSourceTable = null,
        string $whereInSourceCompanyColumn = 'company_id'
    ): int {
        $query = DB::table($table);

        if ($whereInColumn && $whereInSourceTable && Schema::hasTable($whereInSourceTable)) {
            $ids = DB::table($whereInSourceTable)
                ->when(
                    Schema::hasColumn($whereInSourceTable, $whereInSourceCompanyColumn),
                    fn ($q) => $q->where($whereInSourceCompanyColumn, $companyId)
                )
                ->pluck('id');

            if ($ids->isEmpty()) {
                return 0;
            }

            $query->whereIn($whereInColumn, $ids);
            return $query->delete();
        }

        if (Schema::hasColumn($table, $companyColumn)) {
            $query->where($companyColumn, $companyId);
        }

        return $query->delete();
    }

    protected function resetDocumentNumbers(int $companyId): void
    {
        $candidates = [
            'finance_invoices' => 'invoice_no',
            'finance_supplier_bills' => 'bill_no',
            'finance_payments' => 'payment_no',
            'finance_supplier_bill_payments' => 'payment_no',
            'finance_journal_entries' => 'entry_no',
            'petty_cash_transactions' => 'transaction_no',
            'petty_cash_reconciliations' => 'reconciliation_no',
        ];

        foreach ($candidates as $table => $column) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            $query = DB::table($table);

            if (Schema::hasColumn($table, 'company_id')) {
                $query->where('company_id', $companyId);
            }

            $query->update([
                $column => null,
                'updated_at' => now(),
            ]);
        }
    }

    protected function writeAuditLog(
        int $companyId,
        int $actorId,
        array $options,
        array $deleted
    ): void {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')->insert([
            'user_id'      => $actorId,
            'module'       => 'Finance',
            'action'       => 'Data Flush',
            'description'  => 'Finance data flush executed for company_id ' . $companyId,
            'subject_type' => 'finance_data_flush',
            'subject_id'   => null,
            'route'        => request()?->route()?->getName(),
            'url'          => request()?->fullUrl(),
            'method'       => request()?->method(),
            'ip'           => request()?->ip(),
            'user_agent'   => request()?->userAgent(),
            'meta'         => json_encode([
                'company_id'    => $companyId,
                'options'       => $options,
                'deleted'       => $deleted,
                'deleted_total' => array_sum($deleted),
                'environment'   => app()->environment(),
            ]),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
}