<?php
// File: Modules/Finance/Models/Budget.php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Budget extends Model
{
    use SoftDeletes;

    protected $table = 'finance_budgets';

    protected $fillable = [
        'company_id','name','start_date','end_date','period_type','currency_code',
        'status','notes','created_by','approved_by','approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(BudgetLine::class, 'budget_id');
    }
}