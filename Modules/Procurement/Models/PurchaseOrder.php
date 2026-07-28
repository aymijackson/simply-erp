<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Supplier;
use App\Models\SupplierContact;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'proc_purchase_orders';

    protected $fillable = [
        'company_id',
        'purchase_requisition_id',
        'rfq_id',
        'supplier_quotation_id',
        'supplier_id',
        'supplier_contact_id',
        'po_no',
        'supplier_po_ref',
        'po_date',
        'expected_delivery_date',
        'currency_code',
        'fx_rate',
        'delivery_location_id',
        'delivery_store_id',
        'bill_to_location_id',
        'payment_terms',
        'incoterms',
        'reference',
        'notes',
        'internal_notes',
        'subtotal',
        'discount_total',
        'tax_total',
        'shipping_total',
        'other_charges_total',
        'total_amount',
        'received_amount',
        'billed_amount',
        'status',
        'approved_at',
        'approved_by',
        'issued_at',
        'issued_by',
        'closed_at',
        'closed_by',
        'cancelled_at',
        'cancelled_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_delivery_date' => 'date',
        'approved_at' => 'datetime',
        'issued_at' => 'datetime',
        'closed_at' => 'datetime',
        'cancelled_at' => 'datetime',

        'fx_rate' => 'decimal:6',
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'other_charges_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'received_amount' => 'decimal:2',
        'billed_amount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function lines()
    {
        return $this->hasMany(PurchaseOrderLine::class, 'purchase_order_id');
    }

    public function requisition()
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function rfq()
    {
        return $this->belongsTo(RequestForQuotation::class, 'rfq_id');
    }

    public function supplierQuotation()
    {
        return $this->belongsTo(SupplierQuotation::class, 'supplier_quotation_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function supplierContact()
    {
        return $this->belongsTo(SupplierContact::class, 'supplier_contact_id');
    }

    public function deliveryLocation()
    {
        return $this->belongsTo(\App\Models\Location::class, 'delivery_location_id');
    }

    public function deliveryStore()
    {
        return $this->belongsTo(\App\Models\LocationStore::class, 'delivery_store_id');
    }

    public function billToLocation()
    {
        return $this->belongsTo(\App\Models\Location::class, 'bill_to_location_id');
    }

    public function goodsReceipts()
    {
        return $this->hasMany(ProcGoodsReceipt::class, 'purchase_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function issuer()
    {
        return $this->belongsTo(\App\Models\User::class, 'issued_by');
    }

    public function closer()
    {
        return $this->belongsTo(\App\Models\User::class, 'closed_by');
    }

    public function canceller()
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getDisplayNoAttribute(): string
    {
        return $this->po_no ?: ('PO-' . $this->id);
    }

    public function getTotalReceivedQtyAttribute(): string
    {
        return (string) $this->lines()->sum('received_qty');
    }

    public function getTotalBilledQtyAttribute(): string
    {
        return (string) $this->lines()->sum('billed_qty');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isIssued(): bool
    {
        return $this->status === 'issued';
    }

    public function isPartiallyReceived(): bool
    {
        return $this->status === 'partially_rcv';
    }

    public function isFullyReceived(): bool
    {
        return $this->status === 'fully_rcv';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}