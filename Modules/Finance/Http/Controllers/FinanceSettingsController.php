<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceSettingsController extends Controller
{
    public function index()
    {
        if (!$this->tableExists('setting_groups') || !$this->tableExists('settings')) {
            abort(503, 'Settings tables are not installed yet. Please run the settings migrations/seed.');
        }

        $companyId = auth()->user()->company_id ?? 1;

        $group = DB::table('setting_groups')
            ->where('module', 'finance')
            ->where('code', 'finance_accounting')
            ->first();

        if (!$group) {
            abort(503, 'Finance settings group not found. Run the settings seed.');
        }

        $settings = DB::table('settings')
            ->where('setting_group_id', $group->id)
            ->where('scope', 'company')
            ->where('scope_id', $companyId)
            ->pluck('value', 'key'); // key => value

        // Accounts for dropdowns
        $accounts = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('code')
            ->get(['id','code','name','account_type_id']);

        // Account types (to show category labels if needed)
        $types = DB::table('finance_account_types')->get(['id','category','code','name']);

        return view('finance.settings.index', [
            'group' => $group,
            'companyId' => $companyId,
            'settings' => $settings,
            'accounts' => $accounts,
            'types' => $types,
        ]);
    }

    public function save(Request $request)
    {
        if (!$this->tableExists('setting_groups') || !$this->tableExists('settings')) {
            return response()->json(['message' => 'Settings tables are not installed yet.'], 503);
        }

        $companyId = auth()->user()->company_id ?? 1;

        $group = DB::table('setting_groups')
            ->where('module', 'finance')
            ->where('code', 'finance_accounting')
            ->first();

        if (!$group) {
            return response()->json(['message' => 'Finance settings group not found.'], 422);
        }

        $data = $request->validate([
            'retained_earnings_account_id' => ['required','integer'],
            'income_summary_account_id'    => ['required','integer'],
            'ar_control_account_id'        => ['required','integer'],
            'ap_control_account_id'        => ['required','integer'],

            'default_cash_account_id'      => ['nullable','integer'],
            'vat_output_account_id'        => ['nullable','integer'],
            'vat_input_account_id'         => ['nullable','integer'],
        ]);

        $allowed = [
            'retained_earnings_account_id' => ['type'=>'int','required'=>1,'sort'=>10,'label'=>'Retained Earnings Account','desc'=>'Account used to store accumulated profit/loss.'],
            'income_summary_account_id'    => ['type'=>'int','required'=>1,'sort'=>20,'label'=>'Income Summary Account','desc'=>'Temporary account used during year-end close to clear income/expense.'],
            'ar_control_account_id'        => ['type'=>'int','required'=>1,'sort'=>30,'label'=>'Accounts Receivable (Control)','desc'=>'Control account for customer receivables.'],
            'ap_control_account_id'        => ['type'=>'int','required'=>1,'sort'=>40,'label'=>'Accounts Payable (Control)','desc'=>'Control account for supplier payables.'],
            'default_cash_account_id'      => ['type'=>'int','required'=>0,'sort'=>50,'label'=>'Default Cash/Bank Account','desc'=>'Default bank/cash account for receipts/payments if not specified.'],
            'vat_output_account_id'        => ['type'=>'int','required'=>0,'sort'=>60,'label'=>'VAT Output Account','desc'=>'VAT output (sales VAT) account.'],
            'vat_input_account_id'         => ['type'=>'int','required'=>0,'sort'=>70,'label'=>'VAT Input Account','desc'=>'VAT input (purchase VAT) account.'],
        ];

        // Ensure selected accounts belong to company
        $accountIds = collect($data)->filter(fn($v)=>!empty($v))->values()->all();
        $existsCount = DB::table('finance_accounts')
            ->where('company_id', $companyId)
            ->whereIn('id', $accountIds)
            ->count();

        if ($existsCount !== count($accountIds)) {
            return response()->json(['message' => 'One or more selected accounts are invalid for this company.'], 422);
        }

        DB::transaction(function() use ($group, $companyId, $data, $allowed) {
            foreach ($allowed as $key => $meta) {
                $value = $data[$key] ?? null;

                DB::table('settings')->updateOrInsert(
                    [
                        'setting_group_id' => $group->id,
                        'key' => $key,
                        'scope' => 'company',
                        'scope_id' => $companyId,
                    ],
                    [
                        'label' => $meta['label'],
                        'description' => $meta['desc'],
                        'value' => $value,
                        'value_type' => $meta['type'],
                        'is_sensitive' => 0,
                        'is_required' => $meta['required'],
                        'is_active' => 1,
                        'sort_order' => $meta['sort'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        });

        return response()->json(['message' => 'Finance settings saved successfully.']);
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}