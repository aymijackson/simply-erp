<?php

namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class RawMaterial extends Model
{
    protected $table = 'production_raw_materials';

    protected $fillable = [
        'name', 'code', 'cost', 'description', 'unit_id', 'cost_per_unit', 'restock_level', 'stock_quantity', 'is_active'
    ];

    public function unit()
    {
        return $this->belongsTo(\Modules\Inventory\Models\Product\Unit::class);
    }
}
