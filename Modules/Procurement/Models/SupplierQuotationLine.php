<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierQuotationLine extends Model
{
    protected $table = 'proc_supplier_quotation_lines';

    protected $fillable = [
        'supplier_quotation_id',
        'rfq_line_id',
        'product_id',
        'product_variant_id',
        'description',
        'unit_id',
        'qty',
        'unit_price',
        'discount_percent',
        'discount_amount',
        'tax_code_id',
        'tax_rate_id',
        'tax_rate',
        'tax_amount',
        'line_total',
        'lead_time_days',
        'remarks',
    ];

    protected $casts = [
        'qty' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_percent' => 'decimal:4',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];
}