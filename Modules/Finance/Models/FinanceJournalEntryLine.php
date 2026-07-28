<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceJournalEntryLine extends Model
{
    protected $table = 'finance_journal_entry_lines';

    protected $fillable = [
        'journal_entry_id','account_id','description','debit','credit','memo',
        'currency_code','fx_rate','party_type','party_id','bank_account_id'
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'fx_rate' => 'decimal:6',
    ];
}