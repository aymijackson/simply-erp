<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PettyCashAccount extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'account_code',
        'name',
        'custodian_employee_id',
        'location_id',
        'gl_cash_account_id',
        'gl_expense_clearing_account_id',
        'currency_id',
        'minimum_balance',
        'auto_replenish_suggestion',
        'float_amount',
        'current_balance',
        'status',
        'notes',
        'last_replenished_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'float_amount' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'last_replenished_at' => 'datetime',
        'minimum_balance' => 'decimal:2',
        'auto_replenish_suggestion' => 'boolean',
    ];

    public function transactions()
    {
        return $this->hasMany(PettyCashTransaction::class, 'petty_cash_account_id');
    }

    public function reconciliations()
    {
        return $this->hasMany(PettyCashReconciliation::class, 'petty_cash_account_id');
    }

    public function custodian()
    {
        return $this->belongsTo(\Modules\HRM\Models\Employee::class, 'custodian_employee_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\Models\Location::class, 'location_id');
    }

    public function cashGlAccount()
    {
        return $this->belongsTo(\Modules\Finance\Models\FinanceAccount::class, 'gl_cash_account_id');
    }

    public function clearingGlAccount()
    {
        return $this->belongsTo(\Modules\Finance\Models\FinanceAccount::class, 'gl_expense_clearing_account_id');
    }

    public function currency()
    {
        return $this->belongsTo(\App\Models\Currency::class, 'currency_id');
    }
}