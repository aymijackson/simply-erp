<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductAttributeValue extends Model
{
    //use SoftDeletes;

    protected $fillable = [
        'product_attribute_id',
        'value',
        'deleted_at',
    ];

    public function attribute()
    {
        return $this->belongsTo(ProductAttribute::class,'product_attribute_id');
    }

    public function productVariants()
    {
        return $this->belongsToMany(ProductVariant::class);
    }
}
