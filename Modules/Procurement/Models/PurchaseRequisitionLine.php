<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequisitionLine extends Model
{
    protected $table = 'proc_purchase_requisition_lines';

    protected $fillable = [
        'purchase_requisition_id',
        'product_id',
        'product_variant_id',
        'description',
        'unit_id',
        'qty',
        'estimated_unit_cost',
        'tax_code_id',
        'tax_rate_id',
        'tax_rate',
        'tax_amount',
        'line_total',
        'location_id',
        'store_id',
        'memo',
    ];

    protected $casts = [
        'qty'                 => 'decimal:4',
        'estimated_unit_cost' => 'decimal:4',
        'tax_rate'            => 'decimal:4',
        'tax_amount'          => 'decimal:2',
        'line_total'          => 'decimal:2',
    ];

    public function requisition()
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }
}