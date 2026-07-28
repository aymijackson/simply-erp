<?php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPayment extends Model
{
    protected $table = 'sales_payments';

    protected $fillable = [
        'customer_id','payment_no','payment_date','currency_code','amount_received',
        'method','reference','remarks','status','posted_at','posted_by','voided_at','voided_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'posted_at'    => 'datetime',
        'voided_at'    => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(\Modules\CRM\Models\Customer::class, 'customer_id');
    }

    public function allocations()
    {
        return $this->hasMany(SalesPaymentAllocation::class, 'sales_payment_id');
    }

    public function invoices()
    {
        return $this->belongsToMany(SalesInvoice::class, 'sales_payment_allocations', 'sales_payment_id', 'sales_invoice_id')
            ->withPivot(['amount_applied'])
            ->withTimestamps();
    }

    public function getAllocatedTotalAttribute(): float
    {
        return (float) $this->allocations()->sum('amount_applied');
    }

    public function getUnallocatedAmountAttribute(): float
    {
        return (float) $this->amount_received - (float) $this->allocated_total;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'posted' => 'success',
            'void'   => 'danger',
            default  => 'secondary',
        };
    }
}
