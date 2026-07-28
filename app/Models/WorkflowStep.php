<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowStep extends Model
{
    protected $fillable = [
        'workflow_id',
        'step_order',
        'action_type',
        'action_target',
        'action_value',
        'delay_minutes',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}