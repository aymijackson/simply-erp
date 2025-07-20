<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use App\Models\Subregion;
use App\Models\Country;
use App\Models\Company;

class Region extends Model
{
    protected $fillable = ['name'];
    protected $table = 'regions';

    /**
     * Get the subregions for the region.
     */
    public function subregions()
    {
        return $this->hasMany(Subregion::class);
    }
    /**
     * Get the countries for the region.
     */
    public function countries()
    {
        return $this->hasManyThrough(Country::class, Subregion::class);
    }
}
