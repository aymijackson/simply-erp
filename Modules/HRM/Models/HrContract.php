<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class HrContract extends Model {
    use SoftDeletes;
    protected $table = 'hr_contracts';
    protected $fillable = ['employee_id','job_position_id','job_grade_id','contract_type','start_date','end_date','basic_salary','currency_code','pay_frequency','status','termination_date','termination_reason','notes','created_by','updated_by'];
    protected $casts = ['start_date'=>'date','end_date'=>'date','termination_date'=>'date','basic_salary'=>'decimal:2'];
    public function employee()    { return $this->belongsTo(Employee::class); }
    public function jobPosition() { return $this->belongsTo(HrJobPosition::class,'job_position_id'); }
    public function jobGrade()    { return $this->belongsTo(HrJobGrade::class,'job_grade_id'); }
    public function payslips()    { return $this->hasMany(HrPayslip::class,'contract_id'); }
    public function getIsActiveAttribute(): bool { return $this->status === 'active'; }
}