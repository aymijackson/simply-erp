<?php
namespace Modules\Production\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, BelongsToMany};


class WorkOrderTask extends Model
{
    use SoftDeletes;


    protected $fillable = [
        'work_order_id','work_order_step_id','title','description','sequence_index','status','priority',
        'estimated_minutes','actual_minutes','due_at','started_at','completed_at','location_id','created_by','updated_by'
    ];


    protected $casts = [
        'due_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];


    public function workOrder(): BelongsTo 
    { 
        return $this->belongsTo(WorkOrder::class); 
    }

    public function step(): BelongsTo 
    { 
        return $this->belongsTo(WorkOrderStep::class, 'work_order_step_id'); 
    }
    
    public function assignments(): HasMany 
    { 
        return $this->hasMany(WorkOrderTaskAssignment::class, 'task_id'); 
    }

    public function assignees(): BelongsToMany 
    {
        return $this->belongsToMany(\Modules\HRM\Models\Employee::class, 'work_order_task_assignments', 'work_order_task_id', 'employee_id')->withTimestamps();
    }

    public function timeLogs(): HasMany 
    { 
        return $this->hasMany(WorkOrderTaskTimeLog::class, 'work_order_task_id'); 
    }

    public function checklistItems(): HasMany 
    {   
        return $this->hasMany(WorkOrderTaskChecklistItem::class, 'work_order_task_id'); 
    }
        
    public function dependencies(): HasMany 
    { 
        return $this->hasMany(WorkOrderTaskDependency::class, 'work_order_task_id'); 
    }

    public function dependents(): HasMany 
    { 
        return $this->hasMany(WorkOrderTaskDependency::class, 'depends_on_task_id'); 
    }


    // Computed helpers
    public function getIsBlockedAttribute(): bool 
    {
        return $this->dependencies()->whereHas('dependsOn', fn($q) => $q->where('status','!=','completed'))->exists();
    }


    public function getProgressPercentAttribute(): int 
    {
        $count = $this->checklistItems()->count();
        if ($count === 0) return $this->status === 'completed' ? 100 : 0;
        $done = $this->checklistItems()->where('is_checked', true)->count();
        return (int) round(($done / $count) * 100);
    }
}