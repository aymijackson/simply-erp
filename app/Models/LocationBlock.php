<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationBlock extends Model
{
    protected $fillable = [
        'name',
        'location_id',
    ];

    /**
     * Get the location type that owns the location block.
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

}
