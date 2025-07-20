<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Modules\Inventory\Database\Factories\ProductFactory;

class Product extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'product_code', 'category_id', 'group_id', 'brand_id', 'generic_id', 'model_id',
        'product_name', 'product_description', 'product_price', 'has_instances', 'has_lots', 'has_attributes',
        'default_uom', 'pack_size', 'average_cost', 'single_unit_product_code', 'dimension_group',
        'lot_information', 'warranty_terms', 'is_active'
    ];

    // Product.php
    public function attributeTypes()
    {
        // product_attributes = pivot (product_id, attribute_type_id)
        return $this->belongsToMany(ProductAttributeType::class, 'product_attributes', 'attribute_type_id', 'id');
    }

    // ProductAttributeType.php
    public function values()
    {
        return $this->hasMany(ProductAttributeValue::class, 'attribute_type_id');
    }

    // ProductAttributeValue.php
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_attribute_product_value');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function group()
    {
        return $this->belongsTo(ItemGroup::class, 'group_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function defaultUom()
    {
        return $this->belongsTo(ProductUom::class, 'default_uom');
    }

    public function attributes()          // product_attributes rows
    {
        return $this->hasMany(ProductAttribute::class);
    }
    
    public function unit() {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function instances()
    {
        return $this->hasMany(ProductInstance::class);
    }

    public function priceRecords()
    {
        return $this->hasMany(ProductPriceRecord::class);
    }
}
