<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceJournalEntry extends Model
{
    protected $table = 'finance_journal_entries';

    protected $fillable = [
        'company_id',
        'period_id',
        'entry_no',
        'entry_date',
        'reference',
        'memo',
        'status',
        'source_type',
        'source_id',
        'posted_at',
        'posted_by',
        'reversed_at',
        'reversed_by',
        'reversal_of_id',
    ];

    protected $casts = [
        'entry_date'   => 'date',
        'posted_at'    => 'datetime',
        'reversed_at'  => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(FinanceJournalEntryLine::class, 'journal_entry_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'posted_by', 'id');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reversed_by', 'id');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id', 'id');
    }

    public function reversals(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of_id', 'id');
    }
}