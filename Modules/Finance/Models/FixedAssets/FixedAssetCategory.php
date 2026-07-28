<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAssetCategory extends Model
{
    use SoftDeletes;

    protected $table = 'finance_fixed_asset_categories';

    protected $fillable = [
        'company_id','name','code',
        'default_asset_account_id',
        'default_accum_depr_account_id',
        'default_depr_expense_account_id',
        'default_disposal_gain_account_id',
        'default_disposal_loss_account_id',
        'default_depr_method',
        'default_useful_life_months',
        'default_salvage_value',
        'notes',
        'is_active',
    ];
}