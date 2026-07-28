<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierQuotation extends Model
{
    use SoftDeletes;

    protected $table = 'proc_supplier_quotations';

    protected $fillable = [
        'company_id',
        'rfq_id',
        'rfq_supplier_id',
        'supplier_id',
        'quotation_no',
        'supplier_quote_no',
        'quotation_date',
        'valid_until',
        'currency_code',
        'fx_rate',
        'reference',
        'notes',
        'subtotal',
        'tax_total',
        'discount_total',
        'total_amount',
        'status',
        'submitted_at',
        'submitted_by',
        'reviewed_at',
        'reviewed_by',
        'accepted_at',
        'accepted_by',
        'rejected_at',
        'rejected_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'subtotal' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];
}