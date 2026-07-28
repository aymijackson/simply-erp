<?php
// File: Modules/Finance/Models/BudgetAmount.php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetAmount extends Model
{
    protected $table = 'finance_budget_amounts';

    protected $fillable = ['budget_line_id','period_start','period_end','amount'];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function line()
    {
        return $this->belongsTo(BudgetLine::class, 'budget_line_id');
    }
}