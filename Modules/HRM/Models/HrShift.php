<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
class HrShift extends Model {
    protected $table = 'hr_shifts';
    protected $fillable = ['company_id','name','start_time','end_time','break_minutes','is_overnight','is_active','created_by','updated_by'];
    protected $casts = ['is_overnight'=>'boolean','is_active'=>'boolean'];
    public function rosters() { return $this->hasMany(HrRoster::class,'shift_id'); }
    public function getWorkingHoursAttribute(): float {
        $start = strtotime($this->start_time); $end = strtotime($this->end_time);
        $diff  = $this->is_overnight ? ($end+86400-$start) : ($end-$start);
        return round(($diff-($this->break_minutes*60))/3600,2);
    }
}