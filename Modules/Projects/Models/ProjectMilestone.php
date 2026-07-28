<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class ProjectMilestone extends Model
{
    use SoftDeletes;

    protected $table = 'project_milestones';

    protected $fillable = [
        'company_id',
        'project_id',
        'milestone_code',
        'milestone_name',
        'description',
        'owner_id',
        'status',
        'priority',
        'target_date',
        'completed_at',
        'progress_percent',
        'weight_percent',
        'sort_order',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_date'       => 'date',
        'completed_at'      => 'datetime',
        'progress_percent'  => 'decimal:2',
        'weight_percent'    => 'decimal:2',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}