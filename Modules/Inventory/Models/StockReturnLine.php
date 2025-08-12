<?php

// Modules/Inventory/Models/StockReturnLine.php
namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;

class StockReturnLine extends Model
{
    protected $fillable = ['product_variant_id','qty','unit_cost'];
    public function variant() { return $this->belongsTo(Product\ProductVariant::class,'product_variant_id'); }
}