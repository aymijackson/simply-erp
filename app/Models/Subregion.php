<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subregion extends Model
{
    // 
    protected $fillable = [
        'name',
        'region_id',
    ];

    /**
     * Get the region that owns the subregion.
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }
    /**
     * Get the countries for the subregion.
     */
    public function countries()
    {
        return $this->hasMany(Country::class);
    }
    
}
