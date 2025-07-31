<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore;
use Modules\Inventory\Models\StockTransferLine;

// app/Models/StockTransferLine.php
class StockTransferLine extends Model
{
    protected $fillable = ['product_variant_id','qty','unit_cost'];

    public function transfer()       
    { 
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); 
    }

    public function variant()        
    { 
        return $this->belongsTo(ProductVariant::class,'product_variant_id'); 
    }
}
