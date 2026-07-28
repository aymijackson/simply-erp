<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectInvoice extends Model
{
    use SoftDeletes;

    protected $table = 'project_invoices';

    protected $fillable = [
        'company_id',
        'project_id',
        'customer_id',
        'invoice_no',
        'invoice_date',
        'due_date',
        'billing_method',
        'currency_code',
        'fx_rate',
        'reference',
        'memo',
        'subtotal',
        'tax_total',
        'total_amount',
        'amount_paid',
        'balance_due',
        'status',
        'posted_at',
        'posted_by',
        'voided_at',
        'voided_by',
        'journal_entry_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'invoice_date'  => 'date',
        'due_date'      => 'date',
        'fx_rate'       => 'decimal:6',
        'subtotal'      => 'decimal:2',
        'tax_total'     => 'decimal:2',
        'total_amount'  => 'decimal:2',
        'amount_paid'   => 'decimal:2',
        'balance_due'   => 'decimal:2',
        'posted_at'     => 'datetime',
        'voided_at'     => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function lines()
    {
        return $this->hasMany(ProjectInvoiceLine::class, 'project_invoice_id');
    }
}