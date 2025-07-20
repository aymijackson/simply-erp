<?php

// app/Models/StockAge.php
namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore;

class StockAge extends Model
{
    protected $table      = 'v_stock_age';
    public    $timestamps = false;
    public    $incrementing = false;
    protected $guarded    = [];

    public function location_store()   { return $this->belongsTo(LocationStore::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class,'product_variant_id'); }
}
