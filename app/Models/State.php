<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = [
        'name',
        'country_id',
    ];

    /**
     * Get the country that owns the state.
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the companies for the state.
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }
    /**
     * Get the cities for the state.
     */
    public function cities()
    {
        return $this->hasMany(City::class);
    }
    /**
     * Get the regions for the state.
     */
    public function region()    
    {
        return $this->belongsTo(Region::class);
    }
    /**
     * Get the subregion for the state.
     */
    public function subregion()
    {
        return $this->belongsTo(Subregion::class);
    }    
}
