<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCashAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'petty_cash_account_id',
        'petty_cash_transaction_id',
        'reconciliation_id',
        'action',
        'description',
        'old_values',
        'new_values',
        'performed_by',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];
}