<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectInvoiceLine extends Model
{
    protected $table = 'project_invoice_lines';

    protected $fillable = [
        'company_id',
        'project_invoice_id',
        'project_id',
        'task_id',
        'milestone_id',
        'timesheet_id',
        'source_type',
        'source_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'tax_rate'   => 'decimal:4',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(ProjectInvoice::class, 'project_invoice_id');
    }
}