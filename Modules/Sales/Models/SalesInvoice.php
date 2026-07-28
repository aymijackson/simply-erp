<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Modules\CRM\Models\Customer;
use Modules\Sales\Models\SalesOrder;

class SalesInvoice extends Model
{
    protected $table = 'sales_invoices';

    protected $fillable = [
        'invoice_no',
        'sales_order_id',
        'customer_id',
        'invoice_date',
        'due_date',
        'currency_code',
        'reference',
        'remarks',
        'subtotal',
        'tax_total',
        'grand_total',
        'status',
        'posted_at',
        'posted_by',
        'cancelled_at',
        'cancelled_by',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'posted_at'    => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal'     => 'decimal:4',
        'tax_total'    => 'decimal:4',
        'grand_total'  => 'decimal:4',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class, 'sales_invoice_id');
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'posted'    => 'success',
            'cancelled' => 'danger',
            default     => 'secondary',
        };
    }
}
