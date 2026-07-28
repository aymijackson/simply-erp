<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
class HrApplicant extends Model {
    protected $table = 'hr_applicants';
    protected $fillable = ['job_opening_id','first_name','last_name','email','phone','cv_path','source','referral_name','stage','rating','notes','hired_employee_id','created_by','updated_by'];
    public function jobOpening()    { return $this->belongsTo(HrJobOpening::class,'job_opening_id'); }
    public function hiredEmployee() { return $this->belongsTo(Employee::class,'hired_employee_id'); }
    public function interviews()    { return $this->hasMany(HrInterview::class,'applicant_id'); }
    public function getFullNameAttribute(): string { return trim("{$this->first_name} {$this->last_name}"); }
}