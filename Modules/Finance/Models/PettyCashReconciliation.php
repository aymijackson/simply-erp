<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCashReconciliation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'petty_cash_account_id',
        'reconciliation_no',
        'reconciliation_date',
        'opening_balance',
        'funds_added',
        'expenses_total',
        'refunds_total',
        'closing_balance_system',
        'closing_balance_counted',
        'variance_amount',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'opening_balance' => 'decimal:2',
        'funds_added' => 'decimal:2',
        'expenses_total' => 'decimal:2',
        'refunds_total' => 'decimal:2',
        'closing_balance_system' => 'decimal:2',
        'closing_balance_counted' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function account()
    {
        return $this->belongsTo(PettyCashAccount::class, 'petty_cash_account_id');
    }
}