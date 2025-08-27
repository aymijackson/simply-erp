<?php

namespace Modules\Production\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class WorkOrderTaskAssignment extends Model
{
    protected $fillable = ['work_order_task_id','employee_id','role','assigned_at'];
    protected $casts = ['assigned_at' => 'datetime'];


    public function task(): BelongsTo 
    { 
        return $this->belongsTo(WorkOrderTask::class, 'task_id'); 
    }

    public function employee(): BelongsTo 
    { 
        return $this->belongsTo(\Modules\HRM\Models\Employee::class, 'employee_id'); 
    }
}