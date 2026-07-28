<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAssetDeprRun extends Model
{
    use SoftDeletes;

    protected $table = 'finance_fixed_asset_depr_runs';

    protected $fillable = [
        'company_id','run_no','period_start','period_end','run_date',
        'status','notes','journal_entry_id','posted_at','posted_by','voided_at','voided_by','void_reason'
    ];

    public function lines()
    {
        return $this->hasMany(FixedAssetDeprLine::class, 'depr_run_id');
    }
}