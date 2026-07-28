<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceHealthCheckService
{
    public function run(int $companyId, int $actorId): array
    {
        $checks = [

            $this->checkAccountTypes(),
            $this->checkCoreMappings($companyId),
            $this->checkCompanySettings($companyId),
        
            $this->checkDuplicateAccountCodes($companyId),
            $this->checkAccountsMissingType($companyId),
            $this->checkInvalidParentAccounts($companyId),
        
            $this->checkInactiveAccountsUsed($companyId),
            $this->checkOrphanJournalLines($companyId),
            $this->checkUnbalancedJournals($companyId),
        
            $this->checkBankAccountsGlLinks($companyId),
            $this->checkBankAccountsInvalidGL($companyId),
        
            $this->checkFixedAssetCategoryMappings($companyId),
            $this->checkFixedAssetsMissingAccounts($companyId),
        
            $this->checkDepreciationRunsWithoutLines($companyId),
            $this->checkDepreciationRunsWithoutJournal($companyId),
        
        ];

        $summary = [
            'ok_count' => collect($checks)->where('status', 'ok')->count(),
            'warning_count' => collect($checks)->where('status', 'warning')->count(),
            'error_count' => collect($checks)->where('status', 'error')->count(),
        ];

        $result = [
            'summary' => $summary,
            'checks' => $checks,
        ];

        $this->writeAuditLog($companyId, $actorId, $result);

        return $result;
    }

    protected function checkAccountTypes(): array
    {
        if (!Schema::hasTable('finance_account_types')) {
            return $this->error('Account Types', 'finance_account_types table does not exist.');
        }

        $count = DB::table('finance_account_types')->count();

        if ($count === 0) {
            return $this->error('Account Types', 'No account types found.');
        }

        return $this->ok('Account Types', "Found {$count} account type records.");
    }

    protected function checkCoreMappings(int $companyId): array
    {
        if (!Schema::hasTable('finance_account_mappings')) {
            return $this->error('Account Mappings', 'finance_account_mappings table does not exist.');
        }

        $row = DB::table('finance_account_mappings')
            ->where('company_id', $companyId)
            ->first();

        if (!$row) {
            return $this->error('Account Mappings', 'No finance account mapping found for this company.');
        }

        $missing = [];

        if (empty($row->ar_account_id)) $missing[] = 'AR Account';
        if (empty($row->default_bank_gl_account_id)) $missing[] = 'Default Bank GL Account';

        if (count($missing)) {
            return $this->warning(
                'Account Mappings',
                'Mapping exists but some fields are missing: '.implode(', ', $missing).'.'
            );
        }

        return $this->ok('Account Mappings', 'Core finance account mappings are present.');
    }

    protected function checkCompanySettings(int $companyId): array
    {
        if (!Schema::hasTable('finance_company_settings')) {
            return $this->error('Company Settings', 'finance_company_settings table does not exist.');
        }

        $row = DB::table('finance_company_settings')
            ->where('company_id', $companyId)
            ->first();

        if (!$row) {
            return $this->error('Company Settings', 'No finance_company_settings record found for this company.');
        }

        $missing = [];

        if (empty($row->retained_earnings_account_id)) $missing[] = 'Retained Earnings Account';
        if (empty($row->income_summary_account_id)) $missing[] = 'Income Summary Account';

        if (count($missing)) {
            return $this->warning(
                'Company Settings',
                'Settings exist but some year-close fields are missing: '.implode(', ', $missing).'.'
            );
        }

        return $this->ok('Company Settings', 'Finance company settings are present.');
    }

    protected function checkInactiveAccountsUsed(int $companyId): array
    {
        if (!Schema::hasTable('finance_journal_entry_lines') || !Schema::hasTable('finance_accounts')) {
            return $this->warning('Inactive Accounts Usage', 'Required tables not available.');
        }

        $count = DB::table('finance_journal_entry_lines as l')
            ->join('finance_journal_entries as h', 'h.id', '=', 'l.journal_entry_id')
            ->join('finance_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('h.company_id', $companyId)
            ->where('a.is_active', 0)
            ->count();

        if ($count > 0) {
            return $this->warning('Inactive Accounts Usage', "{$count} journal lines reference inactive accounts.");
        }

        return $this->ok('Inactive Accounts Usage', 'No journal lines reference inactive accounts.');
    }

    protected function checkOrphanJournalLines(int $companyId): array
    {
        if (!Schema::hasTable('finance_journal_entry_lines') || !Schema::hasTable('finance_journal_entries')) {
            return $this->warning('Orphan Journal Lines', 'Required tables not available.');
        }

        $count = DB::table('finance_journal_entry_lines as l')
            ->leftJoin('finance_journal_entries as h', 'h.id', '=', 'l.journal_entry_id')
            ->whereNull('h.id')
            ->count();

        if ($count > 0) {
            return $this->error('Orphan Journal Lines', "{$count} journal lines do not have a valid journal header.");
        }

        return $this->ok('Orphan Journal Lines', 'No orphan journal lines found.');
    }

    protected function checkUnbalancedJournals(int $companyId): array
    {
        if (!Schema::hasTable('finance_journal_entries') || !Schema::hasTable('finance_journal_entry_lines')) {
            return $this->warning('Unbalanced Journals', 'Required tables not available.');
        }

        $rows = DB::table('finance_journal_entries as h')
            ->join('finance_journal_entry_lines as l', 'l.journal_entry_id', '=', 'h.id')
            ->where('h.company_id', $companyId)
            ->select(
                'h.id',
                DB::raw('ROUND(SUM(l.debit),2) as debit_total'),
                DB::raw('ROUND(SUM(l.credit),2) as credit_total')
            )
            ->groupBy('h.id')
            ->havingRaw('ROUND(SUM(l.debit),2) <> ROUND(SUM(l.credit),2)')
            ->count();

        if ($rows > 0) {
            return $this->error('Unbalanced Journals', "{$rows} journal entries are not balanced.");
        }

        return $this->ok('Unbalanced Journals', 'All journal entries are balanced.');
    }

    protected function checkBankAccountsGlLinks(int $companyId): array
    {
        if (!Schema::hasTable('finance_bank_accounts')) {
            return $this->warning('Bank GL Links', 'finance_bank_accounts table does not exist.');
        }

        $count = DB::table('finance_bank_accounts')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('gl_account_id')
                  ->orWhere('gl_account_id', 0);
            })
            ->count();

        if ($count > 0) {
            return $this->warning('Bank GL Links', "{$count} bank/cash accounts are missing GL account links.");
        }

        return $this->ok('Bank GL Links', 'All bank/cash accounts have GL links.');
    }

    protected function checkFixedAssetCategoryMappings(int $companyId): array
    {
        if (!Schema::hasTable('finance_fixed_asset_categories')) {
            return $this->warning('FA Category Mappings', 'finance_fixed_asset_categories table does not exist.');
        }

        $count = DB::table('finance_fixed_asset_categories')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('default_asset_account_id')
                  ->orWhereNull('default_accum_depr_account_id')
                  ->orWhereNull('default_depr_expense_account_id')
                  ->orWhereNull('default_disposal_gain_account_id')
                  ->orWhereNull('default_disposal_loss_account_id');
            })
            ->count();

        if ($count > 0) {
            return $this->warning('FA Category Mappings', "{$count} fixed asset categories have incomplete account mappings.");
        }

        return $this->ok('FA Category Mappings', 'All fixed asset categories have required mappings.');
    }

    protected function checkFixedAssetsMissingAccounts(int $companyId): array
    {
        if (!Schema::hasTable('finance_fixed_assets')) {
            return $this->warning('Fixed Assets Accounts', 'finance_fixed_assets table does not exist.');
        }

        $count = DB::table('finance_fixed_assets')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('asset_account_id')
                  ->orWhereNull('accum_depr_account_id')
                  ->orWhereNull('depr_expense_account_id');
            })
            ->count();

        if ($count > 0) {
            return $this->warning('Fixed Assets Accounts', "{$count} fixed assets are missing account assignments.");
        }

        return $this->ok('Fixed Assets Accounts', 'All fixed assets have core accounts assigned.');
    }

    protected function checkDepreciationRunsWithoutJournal(int $companyId): array
    {
        if (!Schema::hasTable('finance_fixed_asset_depr_runs')) {
            return $this->warning('Depreciation Runs', 'finance_fixed_asset_depr_runs table does not exist.');
        }

        $count = DB::table('finance_fixed_asset_depr_runs')
            ->where('company_id', $companyId)
            ->where('status', 'posted')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('journal_entry_id')
                  ->orWhere('journal_entry_id', 0);
            })
            ->count();

        if ($count > 0) {
            return $this->error('Depreciation Runs', "{$count} posted depreciation runs have no linked journal entry.");
        }

        return $this->ok('Depreciation Runs', 'All posted depreciation runs have linked journals.');
    }

    protected function checkDepreciationRunsWithoutLines(int $companyId): array
    {
        if (!Schema::hasTable('finance_fixed_asset_depr_runs')) {
            return $this->warning(
                'Depreciation Runs',
                'Depreciation run table not found.'
            );
        }
    
        $count = DB::table('finance_fixed_asset_depr_runs as r')
            ->leftJoin('finance_fixed_asset_depr_lines as l','l.depr_run_id','=','r.id')
            ->where('r.company_id',$companyId)
            ->whereNull('l.id')
            ->count();
    
        if ($count > 0) {
            return $this->warning(
                'Depreciation Runs Without Lines',
                "{$count} runs have no depreciation lines."
            );
        }
    
        return $this->ok(
            'Depreciation Runs Without Lines',
            'All runs have depreciation lines.'
        );
    }

    protected function checkDuplicateAccountCodes(int $companyId): array
    {
        $duplicates = DB::table('finance_accounts')
            ->select('code', DB::raw('COUNT(*) as cnt'))
            ->where('company_id',$companyId)
            ->groupBy('code')
            ->having('cnt','>',1)
            ->count();
    
        if ($duplicates > 0) {
            return $this->error(
                'Duplicate Account Codes',
                "{$duplicates} duplicate account codes detected."
            );
        }
    
        return $this->ok(
            'Duplicate Account Codes',
            'No duplicate account codes found.'
        );
    }
    
    protected function checkAccountsMissingType(int $companyId): array
    {
        $count = DB::table('finance_accounts')
            ->where('company_id',$companyId)
            ->whereNull('account_type_id')
            ->count();
    
        if ($count > 0) {
            return $this->error(
                'Accounts Missing Account Type',
                "{$count} accounts have no account_type_id."
            );
        }
    
        return $this->ok(
            'Accounts Missing Account Type',
            'All accounts have valid account types.'
        );
    }
    
    protected function checkInvalidParentAccounts(int $companyId): array
    {
        $count = DB::table('finance_accounts as a')
            ->leftJoin('finance_accounts as p','p.id','=','a.parent_id')
            ->where('a.company_id',$companyId)
            ->whereNotNull('a.parent_id')
            ->whereNull('p.id')
            ->count();
    
        if ($count > 0) {
            return $this->error(
                'Invalid Parent Accounts',
                "{$count} accounts reference missing parent accounts."
            );
        }
    
        return $this->ok(
            'Invalid Parent Accounts',
            'All parent account references are valid.'
        );
    }
    
    protected function checkMappingInactiveAccounts(int $companyId): array
    {
        $mapping = DB::table('finance_account_mappings')
            ->where('company_id',$companyId)
            ->first();
    
        if (!$mapping) {
            return $this->warning(
                'Mapping Inactive Accounts',
                'No account mappings found.'
            );
        }
    
        $ids = [
            $mapping->ar_account_id,
            $mapping->ap_account_id,
            $mapping->sales_revenue_account_id,
            $mapping->default_bank_gl_account_id,
            $mapping->vat_output_account_id
        ];
    
        $inactive = DB::table('finance_accounts')
            ->whereIn('id',$ids)
            ->where('is_active',0)
            ->count();
    
        if ($inactive > 0) {
            return $this->warning(
                'Mapping Inactive Accounts',
                "{$inactive} mappings reference inactive accounts."
            );
        }
    
        return $this->ok(
            'Mapping Inactive Accounts',
            'All mappings reference active accounts.'
        );
    }
    
    protected function checkBankAccountsInvalidGL(int $companyId): array
    {
        if (!Schema::hasTable('finance_bank_accounts')) {
            return $this->warning(
                'Bank GL Accounts',
                'finance_bank_accounts table missing.'
            );
        }
    
        $count = DB::table('finance_bank_accounts as b')
            ->leftJoin('finance_accounts as a','a.id','=','b.gl_account_id')
            ->where('b.company_id',$companyId)
            ->whereNull('a.id')
            ->count();
    
        if ($count > 0) {
            return $this->error(
                'Bank GL Accounts',
                "{$count} bank accounts reference invalid GL accounts."
            );
        }
    
        return $this->ok(
            'Bank GL Accounts',
            'All bank GL accounts are valid.'
        );
    }
    
    protected function writeAuditLog(int $companyId, int $actorId, array $result): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')->insert([
            'user_id'      => $actorId,
            'module'       => 'Finance',
            'action'       => 'Health Check',
            'description'  => 'Finance system health check executed for company_id '.$companyId,
            'subject_type' => 'finance_health_check',
            'subject_id'   => null,
            'route'        => request()?->route()?->getName(),
            'url'          => request()?->fullUrl(),
            'method'       => request()?->method(),
            'ip'           => request()?->ip(),
            'user_agent'   => request()?->userAgent(),
            'meta'         => json_encode([
                'company_id' => $companyId,
                'result' => $result,
            ]),
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    protected function ok(string $name, string $message): array
    {
        return [
            'name' => $name,
            'status' => 'ok',
            'message' => $message,
        ];
    }

    protected function warning(string $name, string $message): array
    {
        return [
            'name' => $name,
            'status' => 'warning',
            'message' => $message,
        ];
    }

    protected function error(string $name, string $message): array
    {
        return [
            'name' => $name,
            'status' => 'error',
            'message' => $message,
        ];
    }
}