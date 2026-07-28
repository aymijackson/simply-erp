<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Models\Customer;

class SalesOrder extends Model
{
    protected $table = 'sales_orders';

    protected $fillable = [
        'order_no',
        'customer_id',
        'order_date',
        'currency_code',
        'status',
        'reference',
        'remarks',
        'subtotal',
        'tax_total',
        'grand_total',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'order_date'   => 'date',
        'approved_at'  => 'datetime',
        'subtotal'     => 'decimal:2',
        'tax_total'    => 'decimal:2',
        'grand_total'  => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
    
    public function deliveries()
    {
        return $this->hasMany(SalesDelivery::class, 'sales_order_id');
    }

    public function lines()
    {
        return $this->hasMany(SalesOrderLine::class, 'sales_order_id');
    }
}
