<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\ProductAttributeValueFactory;

class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = ['product_attribute_id', 'value'];

    public function productAttribute()
    {
        return $this->belongsTo(ProductAttribute::class);
    }
}
