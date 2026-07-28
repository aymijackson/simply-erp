<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;

class HrJobPosition extends Model
{
    protected $table = 'hr_job_positions';

    protected $fillable = [
        'company_id',
        'title',
        'department_id',
        'job_grade_id',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function grade()
    {
        return $this->belongsTo(HrJobGrade::class, 'job_grade_id');
    }

    public function department()
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function openings()
    {
        return $this->hasMany(HrJobOpening::class, 'job_position_id');
    }

    public function contracts()
    {
        return $this->hasMany(HrContract::class, 'job_position_id');
    }
}
