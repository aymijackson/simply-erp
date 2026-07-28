<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
class HrPayslipLine extends Model {
    protected $table = 'hr_payslip_lines';
    protected $fillable = ['payslip_id','type','code','description','amount','gl_account_id'];
    protected $casts = ['amount'=>'decimal:2'];
    public function payslip() { return $this->belongsTo(HrPayslip::class); }
}