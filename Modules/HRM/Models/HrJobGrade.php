<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
class HrJobGrade extends Model {
    protected $table = 'hr_job_grades';
    protected $fillable = ['company_id','name','code','min_salary','max_salary','created_by','updated_by'];
    protected $casts = ['min_salary'=>'decimal:2','max_salary'=>'decimal:2'];
    public function positions() { return $this->hasMany(HrJobPosition::class,'job_grade_id'); }
    public function contracts() { return $this->hasMany(HrContract::class,'job_grade_id'); }
}