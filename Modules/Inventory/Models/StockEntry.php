<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Inventory\Models\StockEntryLine;
use Modules\Inventory\Models\Product\ProductVariant;
use App\Models\LocationStore;
use App\Models\Supplier;
use Modules\CRM\Models\Customer;

class StockEntry extends Model
{
    use HasFactory;

    protected $table = 'stock_entries';

    protected $fillable = [
        'reference',
        'reference_type',
        'reference_id',
        'store_id',
        'supplier_id',
        'customer_id',
        'purchase_order_id',
        'purchase_requisition_id',
        'status',
        'entry_type',
        'remarks',
        'sales_delivery_line_id',
        'sales_invoice_line_id',
        'entry_date',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',

        // from Inventory module version
        'product_variant_id',
        'shelf_id',
    ];

    protected $casts = [
        'entry_date'  => 'date',
        'approved_at' => 'datetime',
        'posted_at'   => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(StockEntryLine::class, 'stock_entry_id');
    }

    public function store()
    {
        return $this->belongsTo(LocationStore::class, 'store_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function source()
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }

    public function product_variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'stock_entry_lines',
            'stock_entry_id',
            'product_variant_id'
        )
        ->withPivot(['qty', 'unit_cost'])
        ->withTimestamps();
    }
}