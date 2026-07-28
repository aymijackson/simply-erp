<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'first_name','last_name',
        'user_id','company_id',
        'phone','email','license_no','vehicle_no',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        $first = trim((string)$this->first_name);
        $last  = trim((string)($this->last_name ?? ''));
        return trim($first . ' ' . $last) ?: $first ?: 'Driver';
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    public function deliveries()
    {
        return $this->hasMany(\Modules\Sales\Models\SalesDelivery::class, 'driver_id');
    }
    
    public function vehicles()
    {
        return $this->belongsToMany(\App\Models\Vehicle::class, 'driver_vehicle')
            ->withPivot(['is_primary','assigned_at','unassigned_at'])
            ->withTimestamps();
    }

}
