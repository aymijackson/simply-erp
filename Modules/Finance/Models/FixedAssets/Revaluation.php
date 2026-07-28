<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Revaluation extends Model
{
    use SoftDeletes;

    protected $table = 'finance_fixed_asset_revaluations';

    protected $fillable = [
        'company_id','asset_id','reval_date',
        'old_cost','new_cost','delta',
        'method','revaluation_account_id',
        'memo','status','journal_entry_id',
        'posted_at','posted_by','voided_at','voided_by','void_reason'
    ];
}