<?php

namespace Modules\Projects\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Modules\CRM\Models\Customer;

class Project extends Model
{
    use SoftDeletes;

    protected $table = 'projects';

    protected $fillable = [
        'company_id',
        'project_code',
        'project_name',
        'client_id',
        'project_manager_id',
        'status',
        'priority',
        'start_date',
        'end_date',
        'budget',
        'actual_cost',
        'description',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'budget'      => 'decimal:2',
        'actual_cost' => 'decimal:2',
    ];

    public function client()
    {
        return $this->belongsTo(Customer::class, 'client_id');
    }

    public function projectManager()
    {
        return $this->belongsTo(User::class, 'project_manager_id');
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