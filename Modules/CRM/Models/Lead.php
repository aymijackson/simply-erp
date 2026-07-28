<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HRM\Models\Employee;
use App\Models\Company;
use Modules\CRM\Traits\HasNotes;
use App\Models\User;

class Lead extends Model
{
    use HasFactory, HasNotes;

    protected $fillable = [
        'lead_name',
        'email',
        'phone',
        'company',
        'company_id',
        'position',
        'source',
        'status',
        'notes',
        'follow_up_date',
        'assigned_to',

        // robust fields
        'created_by',
        'updated_by',
        'status_changed_at',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'status_changed_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted()
    {
        static::creating(function (self $lead) {
            if (auth()->check()) {
                $lead->created_by ??= auth()->id();
                $lead->updated_by ??= auth()->id();
            }
            if (!empty($lead->status)) {
                $lead->status_changed_at ??= now();
            }
        });

        static::updating(function (self $lead) {
            if (auth()->check()) {
                $lead->updated_by = auth()->id();
            }
            if ($lead->isDirty('status')) {
                $lead->status_changed_at = now();
            }
        });
    }

    protected static function newFactory()
    {
        return \Modules\CRM\Database\factories\LeadFactory::new();
    }
}
