<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\ProductAttributeFactory;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'attribute_type_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeType()
    {
        return $this->belongsTo(AttributeType::class, 'attribute_type_id');
    }

    public function attributeValues()
    {
        return $this->hasMany(ProductAttributeValue::class);
    }
}
