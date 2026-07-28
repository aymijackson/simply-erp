<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
class HrLeaveType extends Model {
    protected $table = 'hr_leave_types';
    protected $fillable = ['company_id','name','code','days_allowed','carry_over_days','is_paid','requires_approval','gender_restriction','is_active','created_by','updated_by'];
    protected $casts = ['is_paid'=>'boolean','requires_approval'=>'boolean','is_active'=>'boolean','days_allowed'=>'decimal:2','carry_over_days'=>'decimal:2'];
    public function balances() { return $this->hasMany(HrLeaveBalance::class,'leave_type_id'); }
    public function leaves()   { return $this->hasMany(Leave::class,'leave_type_id'); }
}