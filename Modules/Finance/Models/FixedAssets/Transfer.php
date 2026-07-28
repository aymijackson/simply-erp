<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use SoftDeletes;

    protected $table = 'finance_fixed_asset_transfers';

    protected $fillable = [
        'company_id','asset_id','transfer_date',
        'from_location','to_location','from_department','to_department',
        'memo','status','journal_entry_id',
        'posted_at','posted_by','voided_at','voided_by','void_reason'
    ];
}