<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\HRM\Models\Employee;
use Modules\CRM\Traits\HasNotes;
use App\Models\User;

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
        'created_by',
        'updated_by',
        'status_changed_at',
        'related_type',
        'related_id',
    ];

    protected $casts = [
        'due_date'          => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = $model->created_by ?: auth()->id();
                $model->updated_by = $model->updated_by ?: auth()->id();
            }

            // if you want it set on create:
            if (empty($model->status_changed_at) && !empty($model->status)) {
                $model->status_changed_at = now();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }

            // automatically track when status changes
            if ($model->isDirty('status')) {
                $model->status_changed_at = now();
            }
        });
    }

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

    // Audit/analytics actors
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
