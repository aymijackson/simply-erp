<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;

class FixedAssetDeprLine extends Model
{
    protected $table = 'finance_fixed_asset_depr_lines';

    protected $fillable = [
        'depr_run_id',
        'asset_id',
        'opening_nbv',
        'depreciation_amount',
        'closing_nbv',
        'accumulated_depreciation',
    ];

    protected $casts = [
        'opening_nbv' => 'decimal:2',
        'depreciation_amount' => 'decimal:2',
        'closing_nbv' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
    ];

    public function run()
    {
        return $this->belongsTo(FixedAssetDeprRun::class, 'depr_run_id');
    }

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }
}
