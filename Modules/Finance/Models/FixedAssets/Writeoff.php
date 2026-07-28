<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Writeoff extends Model
{
    use SoftDeletes;

    protected $table = 'finance_fixed_asset_writeoffs';

    protected $fillable = [
        'company_id','asset_id','writeoff_date','memo',
        'status','journal_entry_id',
        'posted_at','posted_by','voided_at','voided_by','void_reason'
    ];
}