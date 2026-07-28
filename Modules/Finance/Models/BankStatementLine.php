<?php
// File: Modules/Finance/Models/BankStatementLine.php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankStatementLine extends Model
{
    use SoftDeletes;

    protected $table = 'finance_bank_statement_lines';

    protected $fillable = [
        'company_id','reconciliation_id',
        'txn_date','description','reference','amount',
        'fit_id','raw_payload',
        'status','exclude_reason',
    ];

    protected $casts = [
        'txn_date' => 'date',
        'raw_payload' => 'array',
    ];

    public function reconciliation()
    {
        return $this->belongsTo(BankReconciliation::class, 'reconciliation_id');
    }

    public function match()
    {
        return $this->hasOne(BankStatementMatch::class, 'statement_line_id');
    }
}