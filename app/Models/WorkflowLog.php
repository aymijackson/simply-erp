<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowLog extends Model
{
    protected $fillable = [
        'workflow_id',
        'reference_type',
        'reference_id',
        'status',
        'message'
    ];
}