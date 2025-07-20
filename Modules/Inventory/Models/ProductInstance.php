<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\ProductInstanceFactory;

class ProductInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'instance_name', 'serial_number', 'brand_id',
        'stock_id', 'product_lot_id', 'warranty_terms', 'product_attribute_value_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function productLot()
    {
        return $this->belongsTo(ProductLot::class);
    }

    public function attributeValue()
    {
        return $this->belongsTo(ProductAttributeValue::class, 'product_attribute_value_id');
    }
}
