<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Models\Product\ProductVariant;

class SalesOrderLine extends Model
{
    protected $table = 'sales_order_lines';

    protected $fillable = [
        'sales_order_id',
        'product_variant_id',
        'description',
        'qty_ordered',
        'qty_delivered',
        'unit_price',
        // line_total is stored generated in DB, do not fill it
    ];

    protected $casts = [
        'qty_ordered'   => 'decimal:4',
        'qty_delivered' => 'decimal:4',
        'unit_price'    => 'decimal:4',
    ];

    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
