<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
class HrLeaveBalance extends Model {
    protected $table = 'hr_leave_balances';
    protected $fillable = ['employee_id','leave_type_id','fiscal_year','days_entitled','days_taken','days_carried'];
    protected $casts = ['days_entitled'=>'decimal:2','days_taken'=>'decimal:2','days_carried'=>'decimal:2'];
    public function employee()  { return $this->belongsTo(Employee::class); }
    public function leaveType() { return $this->belongsTo(HrLeaveType::class,'leave_type_id'); }
    public function getDaysRemainingAttribute(): float { return max(0,(float)$this->days_entitled+(float)$this->days_carried-(float)$this->days_taken); }
}