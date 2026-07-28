<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
class HrRoster extends Model {
    protected $table = 'hr_rosters';
    protected $fillable = ['employee_id','shift_id','roster_date','note','created_by'];
    protected $casts = ['roster_date'=>'date'];
    public function employee() { return $this->belongsTo(Employee::class); }
    public function shift()    { return $this->belongsTo(HrShift::class,'shift_id'); }
}