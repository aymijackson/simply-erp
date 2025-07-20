<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\AttributeTypeFactory;

class ProductAttributeType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class, 'attribute_type_id');
    }

    public function values()
    {
        return $this->hasMany(ProductAttribute::class, 'attribute_type_id');
    }
}
