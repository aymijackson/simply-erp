<?php

namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use \Modules\Inventory\Models\Product\Product;

class BomHeader extends Model
{
    protected $fillable = ['company_id','product_variant_id','name', 'bom_code','description','yield_qty','status'];

    public function product_variant()  
    { 
        return $this->belongsTo(\Modules\Inventory\Models\Product\ProductVariant::class,'product_variant_id'); 
    }

    public function items()    
    { 
        return $this->hasMany(BomItem::class); 
    }

    // 3A) Products (read-only) via join — returns distinct products used by this BOM
    // Note: this is not writable (no attaching/detaching).
    public function products()
    {
        return $this->belongsToMany(
            \App\Models\Product::class,
            'bom_items',                         // still use bom_items as the pivot
            'bom_header_id',
            'product_variant_id'                 // maps to product_variants.id; we’ll join to resolve product_id
        )
        ->join('product_variants', 'product_variants.id', '=', 'bom_items.product_variant_id')
        ->whereColumn('products.id', 'product_variants.product_id')
        ->select('products.*')
        ->distinct();
    }
}
