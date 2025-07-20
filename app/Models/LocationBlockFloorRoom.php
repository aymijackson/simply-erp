<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationBlockFloorRoom extends Model
{
    protected $fillable = [
        'name',
        'location_block_floor_id',
    ];
    /**
     * Get the location block floor that owns the location block floor room.
     */
    
    public function floor()    
    {
        return $this->belongsTo(LocationBlockFloor::class, 'location_block_floor_id');
    }
}
