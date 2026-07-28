<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAssetComponent extends Model
{
    use SoftDeletes;

    protected $table = 'finance_fixed_asset_components';

    protected $fillable = [
        'company_id','parent_asset_id','component_code','name','description',
        'cost','salvage_value','depr_method','useful_life_months','depr_rate',
        'asset_account_id','accum_depr_account_id','depr_expense_account_id',
        'status'
    ];
}