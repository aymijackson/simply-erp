<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Supplier;

class PettyCashTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'petty_cash_account_id',
        'transaction_no',
        'voucher_no',
        'transaction_date',
        'type',
        'reference_no',
        'payee_type',
        'payee_id',
        'payee',
        'description',
        'amount',
        'status',
        'workflow_status',
        'expense_account_id',
        'finance_journal_entry_id',
        'attachment',
        'attachment_original_name',
        'attachment_mime_type',
        'attachment_size',
        'approval_notes',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(PettyCashAccount::class, 'petty_cash_account_id');
    }
    
    public function documentLinks()
    {
        return $this->morphMany(\Modules\Document\Models\DocumentLink::class, 'linkable');
    }
    
    public function documents()
    {
        return $this->morphToMany(
            \Modules\Document\Models\Document::class,
            'linkable',
            'document_links',
            'linkable_id',
            'document_id'
        )->wherePivot('linkable_type', self::class);
    }

    public function expenseAccount()
    {
        return $this->belongsTo(\Modules\Finance\Models\FinanceAccount::class, 'expense_account_id');
    }

    public function journalEntry()
    {
        return $this->belongsTo(\Modules\Finance\Models\FinanceJournalEntry::class, 'finance_journal_entry_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    public function postedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'posted_by');
    }

    public function employeePayee()
    {
        return $this->belongsTo(\Modules\HRM\Models\Employee::class, 'payee_id');
    }

    public function supplierPayee()
    {
        return $this->belongsTo(Supplier::class, 'payee_id');
    }

    public function customerPayee()
    {
        return $this->belongsTo(\Modules\CRM\Models\Customer::class, 'payee_id');
    }

    public function getPayeeDisplayAttribute()
    {
        if ($this->payee_type === 'employee' && $this->employeePayee) {
            return trim(($this->employeePayee->first_name ?? '') . ' ' . ($this->employeePayee->last_name ?? ''));
        }

        if ($this->payee_type === 'supplier' && $this->supplierPayee) {
            return $this->supplierPayee->name ?? $this->payee;
        }

        if ($this->payee_type === 'customer' && $this->customerPayee) {
            return $this->customerPayee->name ?? $this->payee;
        }

        return $this->payee;
    }
}