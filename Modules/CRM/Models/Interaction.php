<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\HRM\Models\Employee;

class Interaction extends Model
{
    protected $fillable = [
        'subject',
        'details',
        'interaction_type',
        'interaction_date',
        'employee_id',
        'interactable_type',
        'interactable_id',
    ];

    protected $casts = [
        'interaction_date' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function interactable()
    {
        return $this->morphTo(__FUNCTION__, 'interactable_type', 'interactable_id');
    }
}
