<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectCost extends Model
{
    use SoftDeletes;

    protected $table = 'project_costs';

    protected $fillable = [
        'company_id',
        'project_id',
        'task_id',
        'milestone_id',
        'cost_date',
        'cost_category',
        'source_type',
        'source_id',
        'reference_no',
        'description',
        'quantity',
        'unit_cost',
        'amount',
        'currency_code',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cost_date'   => 'date',
        'quantity'    => 'decimal:2',
        'unit_cost'   => 'decimal:2',
        'amount'      => 'decimal:2',
    ];

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