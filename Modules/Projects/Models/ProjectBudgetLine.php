<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectBudgetLine extends Model
{
    protected $table = 'project_budget_lines';

    protected $fillable = [
        'company_id',
        'project_budget_id',
        'project_id',
        'task_id',
        'milestone_id',
        'cost_category',
        'line_description',
        'quantity',
        'unit_cost',
        'budget_amount',
        'notes',
    ];

    protected $casts = [
        'quantity'      => 'decimal:2',
        'unit_cost'     => 'decimal:2',
        'budget_amount' => 'decimal:2',
    ];

    public function budget()
    {
        return $this->belongsTo(ProjectBudget::class, 'project_budget_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }

    public function milestone()
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }
}