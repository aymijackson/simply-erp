<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountMapping extends Model
{
    use HasFactory;

    protected $table = 'finance_account_mappings';

    protected $fillable = [
        'company_id',
        'ar_account_id',
        'ap_account_id',
        'sales_revenue_account_id',
        'cogs_account_id',
        'inventory_asset_account_id',
        'retained_earnings_account_id',
        'sales_discount_account_id',
        'purchase_discount_account_id',
        'rounding_account_id',
        'default_bank_gl_account_id',
        'vat_output_account_id',
        'vat_input_account_id',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'ar_account_id' => 'integer',
        'ap_account_id' => 'integer',
        'sales_revenue_account_id' => 'integer',
        'cogs_account_id' => 'integer',
        'inventory_asset_account_id' => 'integer',
        'retained_earnings_account_id' => 'integer',
        'sales_discount_account_id' => 'integer',
        'purchase_discount_account_id' => 'integer',
        'rounding_account_id' => 'integer',
        'default_bank_gl_account_id' => 'integer',
        'vat_output_account_id' => 'integer',
        'vat_input_account_id' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id', 'id');
    }

    public function arAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'ar_account_id', 'id');
    }

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'ap_account_id', 'id');
    }

    public function salesRevenueAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'sales_revenue_account_id', 'id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'cogs_account_id', 'id');
    }

    public function inventoryAssetAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'inventory_asset_account_id', 'id');
    }

    public function retainedEarningsAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'retained_earnings_account_id', 'id');
    }

    public function salesDiscountAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'sales_discount_account_id', 'id');
    }

    public function purchaseDiscountAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'purchase_discount_account_id', 'id');
    }

    public function roundingAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'rounding_account_id', 'id');
    }

    public function defaultBankGlAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'default_bank_gl_account_id', 'id');
    }

    public function vatOutputAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'vat_output_account_id', 'id');
    }

    public function vatInputAccount(): BelongsTo
    {
        return $this->belongsTo(FinanceAccount::class, 'vat_input_account_id', 'id');
    }
}