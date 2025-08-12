<?php

namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use \Modules\Inventory\Models\Product\Product;

class BomHeader extends Model
{
    protected $fillable = ['product_variant_id','bom_code','description','yield_qty','status'];

    public function product_variant()  
    { 
        return $this->belongsTo(\Modules\Inventory\Models\Product\ProductVariant::class,'product_variant_id'); 
    }

    public function items()    
    { 
        return $this->hasMany(BomItem::class); 
    }

    /* convenience – total cost, etc. can be added later */
}
