<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore;

class StockTransfer extends Model
{
    protected $fillable = ['transfer_no','from_store_id','to_store_id','reason',
                           'requested_by','approved_by','posted_at','status'];

    public function lines() { return $this->hasMany(StockTransferLine::class); }
    public function fromStore() { return $this->belongsTo(LocationStore::class, 'from_store_id'); }
    public function toStore()   { return $this->belongsTo(LocationStore::class, 'to_store_id'); }
}

// app/Models/StockTransferLine.php
class StockTransferLine extends Model
{
    protected $fillable = ['product_variant_id','qty','unit_cost'];
    public function transfer()       { return $this->belongsTo(StockTransfer::class); }
    public function variant()        { return $this->belongsTo(ProductVariant::class,'product_variant_id'); }
}
