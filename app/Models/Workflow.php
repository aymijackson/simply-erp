<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workflow extends Model
{
    protected $fillable = [
        'name',
        'module',
        'trigger_event',
        'is_active'
    ];

    public function steps()
    {
        return $this->hasMany(WorkflowStep::class);
    }
}