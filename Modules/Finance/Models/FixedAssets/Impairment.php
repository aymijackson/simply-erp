<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Impairment extends Model
{
    use SoftDeletes;

    protected $table = 'finance_fixed_asset_impairments';

    protected $fillable = [
        'company_id','asset_id','impair_date','impair_amount',
        'impair_expense_account_id','memo',
        'status','journal_entry_id',
        'posted_at','posted_by','voided_at','voided_by','void_reason'
    ];
}