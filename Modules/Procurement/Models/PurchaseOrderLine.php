<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\Product\Product;
use Modules\Inventory\Models\Product\ProductVariant;
use Modules\Inventory\Models\Product\Unit;

class PurchaseOrderLine extends Model
{
    use HasFactory;

    protected $table = 'proc_purchase_order_lines';

    protected $fillable = [
        'purchase_order_id',
        'purchase_requisition_line_id',
        'rfq_line_id',
        'supplier_quotation_line_id',
        'product_id',
        'product_variant_id',
        'description',
        'unit_id',
        'location_id',
        'store_id',
        'qty',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_code_id',
        'tax_rate_id',
        'tax_rate',
        'tax_amount',
        'shipping_amount',
        'other_charges_amount',
        'line_total',
        'lead_time_days',
        'expected_delivery_date',
        'received_qty',
        'billed_qty',
        'is_closed',
        'remarks',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_percent' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'other_charges_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
        'received_qty' => 'decimal:4',
        'billed_qty' => 'decimal:4',
        'expected_delivery_date' => 'date',
        'is_closed' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function purchaseRequisitionLine()
    {
        return $this->belongsTo(PurchaseRequisitionLine::class, 'purchase_requisition_line_id');
    }

    public function rfqLine()
    {
        return $this->belongsTo(RequestForQuotationLine::class, 'rfq_line_id');
    }

    public function supplierQuotationLine()
    {
        return $this->belongsTo(SupplierQuotationLine::class, 'supplier_quotation_line_id');
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

    public function location()
    {
        return $this->belongsTo(\App\Models\Location::class, 'location_id');
    }

    public function store()
    {
        return $this->belongsTo(\App\Models\LocationStore::class, 'store_id');
    }

    public function taxCode()
    {
        return $this->belongsTo(\App\Models\TaxCode::class, 'tax_code_id');
    }

    public function taxRate()
    {
        return $this->belongsTo(\App\Models\TaxRate::class, 'tax_rate_id');
    }

    public function goodsReceiptLines()
    {
        return $this->hasMany(ProcGoodsReceiptLine::class, 'purchase_order_line_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getRemainingQtyAttribute(): string
    {
        $qty = (float) $this->qty;
        $received = (float) $this->received_qty;

        return (string) max(0, $qty - $received);
    }

    public function getOutstandingBillQtyAttribute(): string
    {
        $qty = (float) $this->qty;
        $billed = (float) $this->billed_qty;

        return (string) max(0, $qty - $billed);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isFullyReceived(): bool
    {
        return (float) $this->received_qty >= (float) $this->qty;
    }

    public function isPartiallyReceived(): bool
    {
        return (float) $this->received_qty > 0 && (float) $this->received_qty < (float) $this->qty;
    }
}