<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'company_id','registration_no','make','model','color','year','vin','notes','is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'year' => 'integer',
    ];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    public function drivers()
    {
        return $this->belongsToMany(\App\Models\Driver::class, 'driver_vehicle')
            ->withPivot(['is_primary','assigned_at','unassigned_at'])
            ->withTimestamps();
    }
}
