<?php

namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $fillable = [
        'work_order_number', 'product_variant_id', 'bom_header_id', 'quantity_to_produce', 'routing_id', 'status', 'start_date', 'end_date', 'notes'
    ];

    public function bom()
    {
        return $this->belongsTo(BomHeader::class, 'bom_header_id');
    }

    public function steps()
    {
        return $this->hasMany(WorkOrderStep::class);
    }

    public function product_variant()
    {
        return $this->belongsTo(\Modules\Inventory\Models\Product\ProductVariant::class, 'product_variant_id');
    }
}
