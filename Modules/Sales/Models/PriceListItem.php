<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Product\ProductVariant;

class PriceListItem extends Model
{
    protected $table = 'price_list_items';

    protected $fillable = [
        'price_list_id',
        'product_variant_id',
        'unit_price',
        'min_qty',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'min_qty'    => 'decimal:4',
    ];

    public function priceList()
    {
        return $this->belongsTo(PriceList::class, 'price_list_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}