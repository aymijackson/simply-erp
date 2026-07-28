<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectBudget extends Model
{
    use SoftDeletes;

    protected $table = 'project_budgets';

    protected $fillable = [
        'company_id',
        'project_id',
        'budget_code',
        'budget_name',
        'version_no',
        'budget_start_date',
        'budget_end_date',
        'currency_code',
        'total_budget_amount',
        'status',
        'approved_at',
        'approved_by',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'budget_start_date'   => 'date',
        'budget_end_date'     => 'date',
        'total_budget_amount' => 'decimal:2',
        'approved_at'         => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function lines()
    {
        return $this->hasMany(ProjectBudgetLine::class, 'project_budget_id');
    }
}