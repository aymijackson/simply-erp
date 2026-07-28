<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAssetTransaction extends Model
{
    use SoftDeletes;

    protected $table = 'finance_fixed_asset_transactions';

    protected $fillable = [
        'company_id','asset_id','txn_type','txn_date','reference','memo',
        'amount','counter_account_id','bank_account_id',
        'journal_entry_id','status','posted_at','posted_by','voided_at','voided_by','void_reason'
    ];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }
}