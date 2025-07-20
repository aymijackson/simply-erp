<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore;
// use Modules\Inventory\Database\Factories\StockMovementFactory;

class StockTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['product_variant_id', 'location_store_id', 'tx_type', 'qty', 'unit_cost', 'txable_type', 'txable_id', 'tx_date'];

    public function product_variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function location_store()
    {
        return $this->belongsTo(LocationStore::class);
    }

    public function txable()
    {
        return $this->morphTo();
    }
}
