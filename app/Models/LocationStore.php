<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationStore extends Model
{
    protected $fillable = [
        'name',
        'location_id',
        'location_block_floor_room_id',
    ];
    /**
     * Get the location block floor room that owns the location store.
     */
    public function location() 
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function room() 
    {
        return $this->belongsTo(LocationBlockFloorRoom::class, 'location_block_floor_room_id');
    }
}