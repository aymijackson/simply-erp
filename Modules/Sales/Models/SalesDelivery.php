<?php
// Modules/Sales/Models/SalesDelivery.php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class SalesDelivery extends Model
{
    use HasFactory;

    protected $table = 'sales_deliveries';

    protected $fillable = [
        'delivery_no',
        'customer_id',
        'delivery_date',
        'status',          // draft | posted | void
        'remarks',
        'posted_at',
        'posted_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'posted_at'     => 'datetime',
    ];

    /* ─────────────── Relationships ─────────────── */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\CRM\Models\Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesDeliveryLine::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(SalesInvoice::class);   // typical 1-to-1: one delivery → one invoice
    }
}
