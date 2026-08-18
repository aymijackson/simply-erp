<?php

namespace Modules\Finance\Models\FixedAssets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAssetMaintenance extends Model
{
    use SoftDeletes;

    protected $table = 'finance_fixed_asset_maintenance';

    protected $fillable = [
        'company_id', 'asset_id', 'component_id', 'service_date', 'vendor_name',
        'reference_no', 'maintenance_type', 'description', 'cost', 'expense_account_id',
        'status', 'journal_entry_id',
        'posted_at', 'posted_by', 'voided_at', 'voided_by', 'void_reason',
    ];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'asset_id');
    }

    public function component()
    {
        return $this->belongsTo(FixedAssetComponent::class, 'component_id');
    }
}
