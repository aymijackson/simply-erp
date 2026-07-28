<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use SoftDeletes;

    protected $table = 'finance_fixed_assets';

    protected $fillable = [
        'company_id','category_id','asset_code','name','description',
        'purchase_date','in_service_date','purchase_cost','salvage_value',
        'depr_method','useful_life_months','depr_rate',
        'asset_account_id','accum_depr_account_id','depr_expense_account_id',
        'disposal_gain_account_id','disposal_loss_account_id',
        'location','serial_no','supplier_name','invoice_no',
        'status','disposal_date','disposal_proceeds','disposal_notes'
    ];

    public function category()
    {
        return $this->belongsTo(FixedAssetCategory::class, 'category_id');
    }
}