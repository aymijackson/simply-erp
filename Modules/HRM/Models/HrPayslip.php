<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
class HrPayslip extends Model {
    protected $table = 'hr_payslips';
    protected $fillable = ['payroll_run_id','employee_id','contract_id','basic_salary','total_allowances','total_deductions','net_salary','status','paid_at','payment_method','bank_account_id','notes','created_by','updated_by'];
    protected $casts = ['paid_at'=>'datetime','basic_salary'=>'decimal:2','total_allowances'=>'decimal:2','total_deductions'=>'decimal:2','net_salary'=>'decimal:2'];
    public function payrollRun() { return $this->belongsTo(HrPayrollRun::class,'payroll_run_id'); }
    public function employee()   { return $this->belongsTo(Employee::class); }
    public function contract()   { return $this->belongsTo(HrContract::class); }
    public function lines()      { return $this->hasMany(HrPayslipLine::class,'payslip_id'); }
    public function allowances() { return $this->lines()->where('type','allowance'); }
    public function deductions() { return $this->lines()->whereIn('type',['deduction','statutory']); }
}