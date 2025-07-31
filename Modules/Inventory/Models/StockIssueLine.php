<?php

// app/Models/StockLevel.php
namespace Modules\Inventory\Models;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore;

use Illuminate\Database\Eloquent\Model;

class StockIssueLine extends Model
{
    protected $fillable = ['product_variant_id','qty','unit_cost', 'value'];
    
    public function variant() 
    { 
        return $this->belongsTo(ProductVariant::class,'product_variant_id'); 
    }

}
