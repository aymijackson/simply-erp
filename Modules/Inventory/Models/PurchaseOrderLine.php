<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Inventory\Database\Factories\PurchaseOrderLineFactory;

class PurchaseOrderLine extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_order_header_id', 'product_id', 'unit_price', 'quantity'];

    public function orderHeader()
    {
        return $this->belongsTo(PurchaseOrderHeader::class, 'purchase_order_header_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
