<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestForQuotation extends Model
{
    use SoftDeletes;

    protected $table = 'proc_request_for_quotations';

    protected $fillable = [
        'company_id',
        'requisition_id',
        'rfq_no',
        'rfq_date',
        'closing_date',
        'currency_code',
        'fx_rate',
        'status',
        'reference',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
        'subtotal',
        'tax_total',
        'total_amount',
    ];

    protected $casts = [
        'rfq_date'      => 'date',
        'closing_date'  => 'date',
        'approved_at'   => 'datetime',
        'fx_rate'       => 'decimal:6',
        'subtotal'      => 'decimal:2',
        'tax_total'     => 'decimal:2',
        'total_amount'  => 'decimal:2',
    ];

    public function lines()
    {
        return $this->hasMany(RequestForQuotationLine::class, 'rfq_id');
    }

    public function suppliers()
    {
        return $this->hasMany(RequestForQuotationSupplier::class, 'rfq_id');
    }
}