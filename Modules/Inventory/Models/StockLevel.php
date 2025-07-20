<?php

// app/Models/StockLevel.php
namespace Modules\Inventory\Models;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore;

use Illuminate\Database\Eloquent\Model;

class StockLevel extends Model
{
    protected $table      = 'v_stock_levels';
    public    $incrementing = false;
    public    $timestamps   = false;
    protected $guarded      = [];

    /* helpful relationships */
    public function location_store()   { return $this->belongsTo(LocationStore::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class,'product_variant_id'); }
}
