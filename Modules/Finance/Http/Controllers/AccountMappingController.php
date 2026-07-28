<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\Company;
use Modules\Finance\Models\AccountMapping;
use Modules\Finance\Models\FinanceAccount;

class AccountMappingController extends Controller
{
    public function index()
    {
        $companyId = auth()->user()->company_id ?? 1;
    
        $company = Company::find($companyId);
    
        $accounts = FinanceAccount::query()
            ->where('company_id', $companyId)
            ->where('is_active', 1)
            ->orderBy('code')
            ->get(['id','code','name']);
    
        $mapping = AccountMapping::query()
            ->where('company_id', $companyId)
            ->first();
    
        return view(
            'finance.accounts.mappings.index',
            compact('companyId','company','accounts','mapping')
        );
    }
    
    public function upsert(Request $request)
    {
        $companyId = auth()->user()->company_id ?? 1;

        $data = $request->validate([
            'ar_account_id'                => ['required', 'integer'],
            'ap_account_id'                => ['required', 'integer'],
            'sales_revenue_account_id'     => ['nullable', 'integer'],
            'cogs_account_id'              => ['nullable', 'integer'],
            'inventory_asset_account_id'   => ['nullable', 'integer'],
            'retained_earnings_account_id' => ['nullable', 'integer'],
            'sales_discount_account_id'    => ['nullable', 'integer'],
            'purchase_discount_account_id' => ['nullable', 'integer'],
            'rounding_account_id'          => ['nullable', 'integer'],
            'default_bank_gl_account_id'   => ['required', 'integer'],
            'vat_output_account_id'        => ['nullable', 'integer'],
            'vat_input_account_id'         => ['nullable', 'integer'],
        ]);

        $ids = array_values(array_filter([
            $data['ar_account_id'] ?? null,
            $data['ap_account_id'] ?? null,
            $data['sales_revenue_account_id'] ?? null,
            $data['cogs_account_id'] ?? null,
            $data['inventory_asset_account_id'] ?? null,
            $data['retained_earnings_account_id'] ?? null,
            $data['sales_discount_account_id'] ?? null,
            $data['purchase_discount_account_id'] ?? null,
            $data['rounding_account_id'] ?? null,
            $data['default_bank_gl_account_id'] ?? null,
            $data['vat_output_account_id'] ?? null,
            $data['vat_input_account_id'] ?? null,
        ]));

        $validCount = FinanceAccount::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->count();

        if ($validCount !== count($ids)) {
            throw ValidationException::withMessages([
                'accounts' => ['One or more selected accounts are invalid for this company.'],
            ]);
        }

        $mapping = AccountMapping::updateOrCreate(
            ['company_id' => $companyId],
            [
                'ar_account_id'                => $data['ar_account_id'],
                'ap_account_id'                => $data['ap_account_id'],
                'sales_revenue_account_id'     => $data['sales_revenue_account_id'] ?? null,
                'cogs_account_id'              => $data['cogs_account_id'] ?? null,
                'inventory_asset_account_id'   => $data['inventory_asset_account_id'] ?? null,
                'retained_earnings_account_id' => $data['retained_earnings_account_id'] ?? null,
                'sales_discount_account_id'    => $data['sales_discount_account_id'] ?? null,
                'purchase_discount_account_id' => $data['purchase_discount_account_id'] ?? null,
                'rounding_account_id'          => $data['rounding_account_id'] ?? null,
                'default_bank_gl_account_id'   => $data['default_bank_gl_account_id'],
                'vat_output_account_id'        => $data['vat_output_account_id'] ?? null,
                'vat_input_account_id'         => $data['vat_input_account_id'] ?? null,
            ]
        );

        return response()->json([
            'ok'      => true,
            'message' => 'Finance account mappings saved.',
            'data'    => $mapping,
        ]);
    }
}