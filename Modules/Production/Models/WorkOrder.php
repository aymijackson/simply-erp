<?php

namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
    protected $fillable = [
        'work_order_number', 'product_variant_id', 'bill_of_material_id', 'quantity_to_produce', 'routing_id', 'status', 'start_date', 'end_date', 'notes'
    ];

    public function bom()
    {
        return $this->belongsTo(BillOfMaterial::class, 'bill_of_material_id');
    }

    public function steps()
    {
        return $this->hasMany(WorkOrderStep::class);
    }

    public function product()
    {
        return $this->belongsTo(\Modules\Inventory\Models\Product::class);
    }
}
