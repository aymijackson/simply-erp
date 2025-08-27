<?php
namespace Modules\Production\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class WorkOrderTaskDependency extends Model
{
protected $fillable = ['work_order_task_id','depends_on_task_id'];
public function task(): BelongsTo { return $this->belongsTo(WorkOrderTask::class, 'work_order_task_id'); }
public function dependsOn(): BelongsTo { return $this->belongsTo(WorkOrderTask::class, 'depends_on_task_id'); }
}