<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationType extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the locations for the location type.
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
    /**
     * Get the companies for the location type.
     */
    public function companies()
    {
        return $this->hasManyThrough(Company::class, Location::class);
    }
}
