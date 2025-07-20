<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    //  
    protected $fillable = [
        'name',
        'code',
        'subregion_id',
    ];
    /**
     * Get the region that owns the country.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
    
    /**
     * Get the region that owns the country.
     */
    /**
     * Get the subregion that owns the country.
     */
    public function subregion()
    {
        return $this->belongsTo(Subregion::class);
    }
    /**
     * Get the companies for the country.
     */
    public function companies()
    {
        return $this->hasMany(Company::class);
    }
    
}
