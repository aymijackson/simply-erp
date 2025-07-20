<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;
use App\Models\City;
use App\Models\State;
use App\Models\Country;

class SupplierAddress extends Model
{
    protected $fillable = [
        'supplier_id',
        'type',
        'line1',
        'line2',
        'country_id',
        'state_id',
        'city_id',
        'postal_code',
    ];
    /**
     * Get the supplier that owns the address.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    /**
     * Get the city that owns the address.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    /**
     * Get the state that owns the address.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }
    /**
     * Get the country that owns the address.
     */
    public function country()   
    {
        return $this->belongsTo(Country::class);
    }
    /**
     * Get the full address as a formatted string.
     *
     * @return string
     */
    public function getFullAddressAttribute()
    {
        $address = $this->line1;
        if ($this->line_2) {
            $address .= ', ' . $this->line2;
        }
        $address .= ', ' . $this->city->name . ', ' . $this->state->name . ', ' . $this->country->name;
        $address .= ' - ' . $this->postal_code;

        return $address;
    }
    
}
