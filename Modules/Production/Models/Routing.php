<?php

namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\Production\Models\RoutingStep;  
use Modules\Inventory\Models\Product\Product;

class Routing extends Model
{
    protected $fillable = [
        'name', 'description', 'is_active', 'product_variant_id'
    ];

    public function steps()
    {
        return $this->hasMany(RoutingStep::class);
    }

    public function product_variant() 
    { 
        return $this->belongsTo(ProductVariant::class, 'product_variant_id'); 
    }

    public function product() 
    { 
        return $this->hasOneThrough(Product::class, ProductVariant::class, 'id','id','product_variant_id','product_id'); 
    }
}
