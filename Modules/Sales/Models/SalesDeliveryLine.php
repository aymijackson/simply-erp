<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

use Modules\Sales\Models\SalesDelivery;
use Modules\Sales\Models\SalesOrderLine;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore; // adjust namespace

class SalesDeliveryLine extends Model
{
    protected $table = 'sales_delivery_lines';

    protected $fillable = [
        'sales_delivery_id',
        'sales_order_line_id',
        'location_store_id',
        'product_variant_id',
        'qty_to_deliver',
        'qty_delivered_actual',
        'unit_cost',
    ];

    protected $casts = [
        'qty_to_deliver'        => 'float',
        'qty_delivered_actual'  => 'float',
        'unit_cost'             => 'float',
    ];

    public function delivery()
    {
        return $this->belongsTo(SalesDelivery::class, 'sales_delivery_id');
    }

    public function orderLine()
    {
        return $this->belongsTo(SalesOrderLine::class, 'sales_order_line_id');
    }

    public function store()
    {
        return $this->belongsTo(LocationStore::class, 'location_store_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
