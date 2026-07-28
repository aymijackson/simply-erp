<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

class RequestForQuotationSupplier extends Model
{
    protected $table = 'proc_request_for_quotation_suppliers';

    protected $fillable = [
        'rfq_id',
        'supplier_id',
        'sent_at',
        'response_status',
        'contact_name',
        'contact_email',
        'contact_phone',
        'notes',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}