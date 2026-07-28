<?php
// File: Modules/Finance/Models/BankStatementMatch.php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class BankStatementMatch extends Model
{
    protected $table = 'finance_bank_statement_matches';

    protected $fillable = [
        'statement_line_id','journal_entry_line_id',
        'matched_amount','match_method',
        'matched_by','matched_at',
    ];

    protected $casts = [
        'matched_at' => 'datetime',
    ];

    public function statementLine()
    {
        return $this->belongsTo(BankStatementLine::class, 'statement_line_id');
    }

    public function journalEntryLine()
    {
        return $this->belongsTo(JournalEntryLine::class, 'journal_entry_line_id');
    }
}