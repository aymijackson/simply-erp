<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class ProjectTimesheet extends Model
{
    use SoftDeletes;

    protected $table = 'project_timesheets';

    protected $fillable = [
        'company_id',
        'project_id',
        'task_id',
        'milestone_id',
        'employee_id',
        'entry_date',
        'start_time',
        'end_time',
        'hours',
        'hourly_rate',
        'cost_amount',
        'billable_hours',
        'billing_rate',
        'billable_amount',
        'is_billable',
        'status',
        'description',
        'notes',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
        'source_type',
        'source_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'entry_date'      => 'date',
        'hours'           => 'decimal:2',
        'hourly_rate'     => 'decimal:2',
        'cost_amount'     => 'decimal:2',
        'billable_hours'  => 'decimal:2',
        'billing_rate'    => 'decimal:2',
        'billable_amount' => 'decimal:2',
        'is_billable'     => 'boolean',
        'approved_at'     => 'datetime',
        'rejected_at'     => 'datetime',
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

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}