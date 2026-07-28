<?php

namespace App\Models;   

use Illuminate\Database\Eloquent\Model; 
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToThrough;
use App\Models\City;
use App\Models\LocationType;
use App\Models\Company;
use App\Models\Subregion;
use App\Models\Region;
use App\Models\Country;

class Location extends Model
{
    protected $fillable = [
        'name',
        'city_id',
        'address',
        'location_type_id',
        'latitude',
        'longitude',
        'company_id',
        'description',
    ];

    /**
     * Get the city that owns the location.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Get the location type that owns the location.
     */
    public function type()  
    {
        return $this->belongsTo(LocationType::class, 'location_type_id');
    }
    
    public function state()  
    {
        return $this->belongsTo(State::class, 'state_id');
    }
    /**
     * Get the company that owns the location.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    /**
     * Get the subregion that owns the location.
     */
    public function subregion()
    {
        return $this->belongsTo(Subregion::class);
    }
    /**
     * Get the region that owns the location.
     */
    public function region()
    {
        return $this->belongsToThrough(Region::class, Subregion::class);
    }
    /**
     * Get the countries that own the location.
     */
    public function countries()
    {
        return $this->belongsToMany(Country::class, 'country_location', 'location_id', 'country_id');
    }
}
