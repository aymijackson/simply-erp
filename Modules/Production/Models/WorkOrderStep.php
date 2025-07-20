<?php

namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderStep extends Model
{
    protected $fillable = [
        'work_order_id',
        'step_name',
        'description',
        'sequence',
        'performed_by',
        'started_at',
        'completed_at',
        'status'
    ];

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'performed_by');
    }
}
