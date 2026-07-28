<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class ProjectTask extends Model
{
    use SoftDeletes;

    protected $table = 'project_tasks';

    protected $fillable = [
        'company_id',
        'project_id',
        'parent_task_id',
        'task_code',
        'task_name',
        'description',
        'assigned_to',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'estimated_hours',
        'actual_hours',
        'progress_percent',
        'sort_order',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date'       => 'date',
        'due_date'         => 'date',
        'completed_at'     => 'datetime',
        'estimated_hours'  => 'decimal:2',
        'actual_hours'     => 'decimal:2',
        'progress_percent' => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function parentTask()
    {
        return $this->belongsTo(ProjectTask::class, 'parent_task_id');
    }

    public function children()
    {
        return $this->hasMany(ProjectTask::class, 'parent_task_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}