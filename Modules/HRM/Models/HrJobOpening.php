<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class HrJobOpening extends Model {
    use SoftDeletes;
    protected $table = 'hr_job_openings';
    protected $fillable = ['company_id','job_position_id','department_id','title','description','requirements','vacancies','posted_date','closing_date','status','created_by','updated_by'];
    protected $casts = ['posted_date'=>'date','closing_date'=>'date','vacancies'=>'integer'];
    public function jobPosition() { return $this->belongsTo(HrJobPosition::class,'job_position_id'); }
    public function department()  { return $this->belongsTo(\App\Models\Department::class); }
    public function applicants()  { return $this->hasMany(HrApplicant::class,'job_opening_id'); }
}