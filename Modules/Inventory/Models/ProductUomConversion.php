<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\ProductUomConversionFactory;

class ProductUomConversion extends Model
{
    use HasFactory;

    protected $fillable = ['from_uom_id', 'to_uom_id', 'conversion_rule'];

    public function fromUom()
    {
        return $this->belongsTo(ProductUom::class, 'from_uom_id');
    }

    public function toUom()
    {
        return $this->belongsTo(ProductUom::class, 'to_uom_id');
    }
}
