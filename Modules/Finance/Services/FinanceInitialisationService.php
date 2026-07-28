<?php

namespace Modules\Finance\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceInitialisationService
{
    public function preview(int $companyId): array
    {
        return [
            'account_types_existing' => DB::table('finance_account_types')->count(),
            'accounts_existing' => DB::table('finance_accounts')
                ->where('company_id',$companyId)->count(),
            'mappings_existing' => DB::table('finance_account_mappings')
                ->where('company_id',$companyId)->count(),
            'company_settings_existing' => DB::table('finance_company_settings')
                ->where('company_id',$companyId)->count(),
        ];
    }

    public function run(int $companyId, int $actorId): array
    {
        return DB::transaction(function () use ($companyId,$actorId){

            $result = [];

            $result['account_types'] = $this->seedAccountTypes();
            $result['accounts'] = $this->seedAccounts($companyId);
            $result['mappings'] = $this->seedMappings($companyId);
            $result['company_settings'] = $this->seedCompanySettings($companyId);

            $this->writeAuditLog($companyId,$actorId,$result);

            return $result;
        });
    }

    protected function seedAccountTypes(): array
    {
        $types = [
            ['code'=>'ASSET','name'=>'Assets','category'=>'asset','normal_balance'=>'debit'],
            ['code'=>'LIAB','name'=>'Liabilities','category'=>'liability','normal_balance'=>'credit'],
            ['code'=>'EQUITY','name'=>'Equity','category'=>'equity','normal_balance'=>'credit'],
            ['code'=>'INCOME','name'=>'Income','category'=>'income','normal_balance'=>'credit'],
            ['code'=>'EXP','name'=>'Expenses','category'=>'expense','normal_balance'=>'debit'],
        ];

        $created = 0;
        $skipped = 0;

        foreach($types as $row){

            $exists = DB::table('finance_account_types')
                ->where('code',$row['code'])
                ->first();

            if($exists){
                $skipped++;
                continue;
            }

            DB::table('finance_account_types')->insert([
                ...$row,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);

            $created++;
        }

        return compact('created','skipped');
    }

    protected function seedAccounts(int $companyId): array
    {
        $types = DB::table('finance_account_types')->pluck('id','code');

        $accounts = [
            ['code'=>'1000','name'=>'Cash in Hand','type'=>'ASSET'],
            ['code'=>'1010','name'=>'Main Bank Account','type'=>'ASSET'],
            ['code'=>'1100','name'=>'Accounts Receivable','type'=>'ASSET'],
            ['code'=>'1200','name'=>'Inventory','type'=>'ASSET'],
            ['code'=>'1300','name'=>'Fixed Assets','type'=>'ASSET'],
            ['code'=>'1310','name'=>'Accumulated Depreciation','type'=>'ASSET'],

            ['code'=>'2000','name'=>'Accounts Payable','type'=>'LIAB'],
            ['code'=>'2100','name'=>'Tax Payable','type'=>'LIAB'],

            ['code'=>'3000','name'=>'Owner Equity','type'=>'EQUITY'],

            ['code'=>'4000','name'=>'Sales Revenue','type'=>'INCOME'],
            ['code'=>'4300','name'=>'Gain on Disposal','type'=>'INCOME'],

            ['code'=>'5000','name'=>'Cost of Sales','type'=>'EXP'],
            ['code'=>'6000','name'=>'Operating Expenses','type'=>'EXP'],
            ['code'=>'6100','name'=>'Depreciation Expense','type'=>'EXP'],
            ['code'=>'6200','name'=>'Maintenance Expense','type'=>'EXP'],
            ['code'=>'6300','name'=>'Loss on Disposal','type'=>'EXP'],
        ];

        $created=0;
        $skipped=0;

        foreach($accounts as $a){

            $exists = DB::table('finance_accounts')
                ->where('company_id',$companyId)
                ->where('code',$a['code'])
                ->first();

            if($exists){
                $skipped++;
                continue;
            }

            DB::table('finance_accounts')->insert([
                'company_id'=>$companyId,
                'account_type_id'=>$types[$a['type']],
                'code'=>$a['code'],
                'name'=>$a['name'],
                'parent_id'=>null,
                'is_control'=>0,
                'allow_manual_posting'=>1,
                'is_active'=>1,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);

            $created++;
        }

        return compact('created','skipped');
    }

    protected function seedMappings(int $companyId): array
    {
        $accounts = DB::table('finance_accounts')
            ->where('company_id',$companyId)
            ->pluck('id','code');

        $exists = DB::table('finance_account_mappings')
            ->where('company_id',$companyId)
            ->first();

        if($exists){
            return ['created'=>0,'skipped'=>1];
        }

        DB::table('finance_account_mappings')->insert([
            'company_id'=>$companyId,
            'ar_account_id'=>$accounts['1100'] ?? null,
            'ap_account_id'=>$accounts['2000'] ?? null,
            'sales_revenue_account_id'=>$accounts['4000'] ?? null,
            'default_bank_gl_account_id'=>$accounts['1010'] ?? null,
            'vat_output_account_id'=>$accounts['2100'] ?? null,
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return ['created'=>1,'skipped'=>0];
    }

    protected function seedCompanySettings(int $companyId): array
    {
        $exists = DB::table('finance_company_settings')
            ->where('company_id',$companyId)
            ->first();

        if($exists){
            return ['created'=>0,'skipped'=>1];
        }

        DB::table('finance_company_settings')->insert([
            'company_id'=>$companyId,
            'retained_earnings_account_id'=>null,
            'income_summary_account_id'=>null,
            'allow_post_to_closed_period'=>0,
            'restrict_future_posting'=>0,
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        return ['created'=>1,'skipped'=>0];
    }

    protected function writeAuditLog(int $companyId,int $actorId,array $results): void
    {
        if(!Schema::hasTable('audit_logs')) return;

        DB::table('audit_logs')->insert([
            'user_id'=>$actorId,
            'module'=>'Finance',
            'action'=>'Initialisation',
            'description'=>'Finance master data initialised',
            'subject_type'=>'finance_initialisation',
            'route'=>request()?->route()?->getName(),
            'url'=>request()?->fullUrl(),
            'method'=>request()?->method(),
            'ip'=>request()?->ip(),
            'user_agent'=>request()?->userAgent(),
            'meta'=>json_encode([
                'company_id'=>$companyId,
                'results'=>$results,
            ]),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);
    }
    
}