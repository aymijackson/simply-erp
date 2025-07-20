<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HRM\Models\Employee;
use App\Models\Company;
use Modules\CRM\Traits\HasNotes;


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
    ];

    protected $dates = ['follow_up_date'];

    /**
     * Get the company associated with the lead.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employee assigned to the lead.
     */
    public function assignedEmployee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    protected static function newFactory()
    {
        return \Modules\CRM\Database\factories\LeadFactory::new();
    }
}
