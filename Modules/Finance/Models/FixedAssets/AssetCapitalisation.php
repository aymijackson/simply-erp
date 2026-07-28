<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetCapitalisation extends Model
{
    use SoftDeletes;

    protected $table = 'finance_asset_capitalisations';

    protected $fillable = [
        'company_id',
        'source_module','source_table','source_id',
        'supplier_id','reference_no',
        'asset_category_id',
        'asset_name','asset_description',
        'quantity','unit_cost','total_cost',
        'purchase_date','in_service_date',
        'status','converted_asset_id','converted_at','converted_by',
        'voided_at','voided_by','void_reason'
    ];
}