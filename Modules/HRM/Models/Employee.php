<?php

namespace Modules\HRM\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use Modules\Document\Traits\HasDocuments;

class Employee extends Authenticatable
{
    use HasDocuments, HasFactory, HasRoles;

    protected $table = 'employees';

    protected $fillable = [
        'user_id',
        'company_id',
        'department_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'date_of_birth',
        'date_hired',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_hired'    => 'date',
        'is_active'     => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }
    
    public function contracts()
    {
        return $this->hasMany(\Modules\HRM\Models\HrContract::class, 'employee_id');
    }
     
    public function activeContract()
    {
        return $this->hasOne(\Modules\HRM\Models\HrContract::class, 'employee_id')
            ->where('status', 'active')
            ->latestOfMany('start_date');
    }
     
    public function rosters()
    {
        return $this->hasMany(\Modules\HRM\Models\HrRoster::class, 'employee_id');
    }
     
    public function leaveBalances()
    {
        return $this->hasMany(\Modules\HRM\Models\HrLeaveBalance::class, 'employee_id');
    }
     
    public function payslips()
    {
        return $this->hasMany(\Modules\HRM\Models\HrPayslip::class, 'employee_id');
    }
}