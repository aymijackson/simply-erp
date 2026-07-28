<?php

namespace Modules\Inventory\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\BrandManufacturer;
use Modules\Inventory\Models\Product\Product;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'category_product',   // pivot table
            'category_id',        // FK to categories
            'product_id'          // FK to products
        )->withTimestamps();
    }
}