<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddress extends Model
{
    protected $table = 'customer_addresses';

    protected $fillable = [
        'customer_id',
        'type',
        'line1',
        'line2',
        'country_id',
        'state_id',
        'city_id',
        'postal_code',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'country_id' => 'integer',
        'state_id'   => 'integer',
        'city_id'    => 'integer',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function country()
    {
        return $this->belongsTo(\App\Models\Country::class, 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(\App\Models\State::class, 'state_id');
    }

    public function city()
    {
        return $this->belongsTo(\App\Models\City::class, 'city_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->line1,
            $this->line2,
            $this->city?->name,
            $this->state?->name,
            $this->country?->name,
            $this->postal_code,
        ])->filter()->implode(', ');
    }
}