<?php
// File: Modules/Finance/Models/BankReconciliation.php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankReconciliation extends Model
{
    use SoftDeletes;

    protected $table = 'finance_bank_reconciliations';

    protected $fillable = [
        'company_id','bank_account_id',
        'period_start','period_end',
        'statement_opening_balance','statement_closing_balance',
        'system_opening_balance','system_closing_balance',
        'status','notes',
        'created_by','closed_by','closed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end'   => 'date',
        'closed_at'    => 'datetime',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'bank_account_id');
    }

    public function statementLines()
    {
        return $this->hasMany(BankStatementLine::class, 'reconciliation_id');
    }
}