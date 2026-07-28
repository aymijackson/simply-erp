<?php
// File: Modules/Finance/Models/BudgetLine.php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;

class BudgetLine extends Model
{
    protected $table = 'finance_budget_lines';

    protected $fillable = ['budget_id','account_id','notes'];

    public function budget()
    {
        return $this->belongsTo(Budget::class, 'budget_id');
    }

    public function amounts()
    {
        return $this->hasMany(BudgetAmount::class, 'budget_line_id');
    }
}