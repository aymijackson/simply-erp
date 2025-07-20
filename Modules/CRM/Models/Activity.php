<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\HRM\Models\Employee;
use Modules\CRM\Traits\HasNotes;

class Activity extends Model
{
    use HasNotes;
    protected $fillable = [
        'subject',
        'description',
        'activity_type',
        'due_date',
        'status',
        'owner_id',
        'related_type',
        'related_id'
    ];

    // Owner (employee assigned to this activity)
    public function owner()
    {
        return $this->belongsTo(Employee::class, 'owner_id');
    }

    // Related model polymorphic relationship (optional)
    public function related()
    {
        return $this->morphTo(__FUNCTION__, 'related_type', 'related_id');
    }
}
