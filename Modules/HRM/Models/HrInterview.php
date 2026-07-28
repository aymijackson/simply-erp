<?php
namespace Modules\HRM\Models;
use Illuminate\Database\Eloquent\Model;
class HrInterview extends Model {
    protected $table = 'hr_interviews';
    protected $fillable = ['applicant_id','interviewer_id','scheduled_at','type','outcome','score','feedback','created_by','updated_by'];
    protected $casts = ['scheduled_at'=>'datetime','score'=>'decimal:2'];
    public function applicant()   { return $this->belongsTo(HrApplicant::class); }
    public function interviewer() { return $this->belongsTo(\App\Models\User::class,'interviewer_id'); }
}