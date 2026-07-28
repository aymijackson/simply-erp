<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Supplier;

class GoodsReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'proc_goods_receipts';

    protected $fillable = [
        'company_id',
        'purchase_order_id',
        'stock_entry_id',
        'supplier_id',
        'grn_no',
        'receipt_date',
        'supplier_delivery_note_no',
        'delivery_location_id',
        'delivery_store_id',
        'reference',
        'notes',
        'subtotal',
        'status',
        'received_by',
        'posted_at',
        'posted_by',
        'cancelled_at',
        'cancelled_by',
        'created_by',
        'updated_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
    ];

    public function lines()
    {
        return $this->hasMany(GoodsReceiptLine::class, 'goods_receipt_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function deliveryLocation()
    {
        return $this->belongsTo(\App\Models\Location::class, 'delivery_location_id');
    }

    public function deliveryStore()
    {
        return $this->belongsTo(\App\Models\LocationStore::class, 'delivery_store_id');
    }

    public function stockEntry()
    {
        return $this->belongsTo(\App\Models\StockEntry::class, 'stock_entry_id');
    }

    public function receiver()
    {
        return $this->belongsTo(\App\Models\User::class, 'received_by');
    }

    public function poster()
    {
        return $this->belongsTo(\App\Models\User::class, 'posted_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function canceller()
    {
        return $this->belongsTo(\App\Models\User::class, 'cancelled_by');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}