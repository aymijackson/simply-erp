<?php

namespace Modules\Inventory\Models\Product;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Product\Product;
use Modules\Inventory\Models\Product\ProductAttributeType;


class ProductAttribute extends Model
{
    use HasFactory;

    protected $table = 'product_attributes';

    protected $fillable = [
        'product_id',
        'attribute_type_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function type()                // Colour, Size, …
    {
        return $this->belongsTo(ProductAttributeType::class,'attribute_type_id');
    }

    public function values()              // Red, Blue, Large …
    {
        return $this->hasMany(ProductAttributeValue::class,'product_attribute_id');
    }
}
