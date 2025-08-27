<?php
namespace Modules\Production\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class WorkOrderTaskChecklistItem extends Model
{
protected $fillable = ['task_id','label','is_required','is_checked','checked_by','checked_at'];
protected $casts = ['is_required' => 'boolean','is_checked' => 'boolean','checked_at' => 'datetime'];


public function task(): BelongsTo { return $this->belongsTo(WorkOrderTask::class, 'task_id'); }
}