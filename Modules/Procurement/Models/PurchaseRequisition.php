<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequisition extends Model
{
    use SoftDeletes;

    protected $table = 'proc_purchase_requisitions';

    protected $fillable = [
        'company_id',
        'requisition_no',
        'requisition_date',
        'needed_by_date',
        'department_id',
        'requested_by',
        'approved_by',
        'approved_at',
        'priority',
        'status',
        'reference',
        'notes',
        'subtotal',
        'tax_total',
        'total_amount',
    ];

    protected $casts = [
        'requisition_date' => 'date',
        'needed_by_date'   => 'date',
        'approved_at'      => 'datetime',
        'subtotal'         => 'decimal:2',
        'tax_total'        => 'decimal:2',
        'total_amount'     => 'decimal:2',
    ];

    public function lines()
    {
        return $this->hasMany(PurchaseRequisitionLine::class, 'purchase_requisition_id');
    }
}