<?php
namespace Modules\Production\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class WorkOrderTaskTimeLog extends Model
{
protected $fillable = ['work_order_task_id','employee_id','started_at','ended_at','minutes','note'];
protected $casts = ['started_at' => 'datetime', 'ended_at' => 'datetime'];


public function task(): BelongsTo { return $this->belongsTo(WorkOrderTask::class, 'work_order_task_id'); }
public function employee(): BelongsTo { return $this->belongsTo(\Modules\HRM\Models\Employee::class, 'employee_id'); }
}