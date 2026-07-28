<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
class HrPayrollRun extends Model {
    protected $table = 'hr_payroll_runs';
    protected $fillable = ['company_id','run_no','period_month','period_year','pay_date','status','total_gross','total_deductions','total_net','journal_entry_id','approved_by','approved_at','posted_by','posted_at','created_by','updated_by'];
    protected $casts = ['pay_date'=>'date','approved_at'=>'datetime','posted_at'=>'datetime','total_gross'=>'decimal:2','total_deductions'=>'decimal:2','total_net'=>'decimal:2'];
    public function payslips() { return $this->hasMany(HrPayslip::class,'payroll_run_id'); }
    public function approver() { return $this->belongsTo(\App\Models\User::class,'approved_by'); }
    public function poster()   { return $this->belongsTo(\App\Models\User::class,'posted_by'); }
    public function getPeriodLabelAttribute(): string { return date('F Y',mktime(0,0,0,$this->period_month,1,$this->period_year)); }
}