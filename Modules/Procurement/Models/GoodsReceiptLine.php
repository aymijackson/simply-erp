<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\Product\Product;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\Inventory\Models\Product\Unit;
class GoodsReceiptLine extends Model
{
    use HasFactory;

    protected $table = 'proc_goods_receipt_lines';

    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_line_id',
        'product_id',
        'product_variant_id',
        'description',
        'unit_id',
        'ordered_qty',
        'previously_received_qty',
        'received_qty',
        'remaining_qty',
        'unit_cost',
        'line_total',
        'accepted_qty',
        'rejected_qty',
        'damage_qty',
        'batch_no',
        'serial_no',
        'expiry_date',
        'remarks',
    ];

    protected $casts = [
        'ordered_qty' => 'decimal:4',
        'previously_received_qty' => 'decimal:4',
        'received_qty' => 'decimal:4',
        'remaining_qty' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:2',
        'accepted_qty' => 'decimal:4',
        'rejected_qty' => 'decimal:4',
        'damage_qty' => 'decimal:4',
        'expiry_date' => 'date',
    ];

    public function goodsReceipt()
    {
        return $this->belongsTo(GoodsReceipt::class, 'goods_receipt_id');
    }

    public function purchaseOrderLine()
    {
        return $this->belongsTo(PurchaseOrderLine::class, 'purchase_order_line_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}