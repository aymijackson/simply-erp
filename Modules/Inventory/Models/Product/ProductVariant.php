<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\BrandFactory;
use Modules\Inventory\Models\Product\BrandManufacturer;
use Modules\Inventory\Models\Product\Product;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'sku',
        'price',
        'stock_quantity',
        'reorder_point',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(ProductAttributeValue::class);
    }

    /* Scope for the report */
    public function scopeLowStock($q)
    {
        return $q->whereColumn('stock_quantity', '<=', 'reorder_point');
    }

    /* Helper accessor (optional) */
    public function getLowStockFlagAttribute(): bool
    {
        return $this->qty_on_hand <= $this->reorder_point;
    }

}