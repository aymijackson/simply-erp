<?php
// Modules/Sales/Models/SalesInvoice.php

namespace Modules\Sales\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};

class SalesInvoice extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'sales_invoices';

    /** @var array<int,string> */
    protected $fillable = [
        'invoice_no',
        'customer_id',
        'invoice_date',
        'due_date',
        'status',          // draft | posted | void
        'currency',
        'remarks',
        'total_before_tax',
        'tax_amount',
        'total_amount',
        'posted_at',
        'posted_by',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'posted_at'    => 'datetime',
        'total_before_tax' => 'float',
        'tax_amount'       => 'float',
        'total_amount'     => 'float',
    ];

    /* ─────────────── Relationships ─────────────── */

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\CRM\Models\Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\Modules\Finance\Models\Payment::class, 'invoice_id');
    }
}
