<?php

namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Inventory\Models\Product\ProductVariant;

class WorkOrderMaterial extends Model
{
    protected $fillable = [
        'work_order_id',
        'bom_item_id',
        'product_variant_id',
        'planned_qty',
        'issued_qty',
        'returned_qty',
        'notes',
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(RawMaterial::class);
    }

    public function product_variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
