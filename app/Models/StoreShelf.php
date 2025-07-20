<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreShelf extends Model
{
    protected $fillable = [
        'code',
        'store_id',
        'description',  
        'capacity',
    ];

    /**
     * Get the store that owns the shelf.
     */
    public function store()
    {
        return $this->belongsTo(LocationStore::class);
    }

    /**
     * Get the location block floor room that owns the shelf.
     */
    public function locationBlockFloorRoom()
    {
        return $this->belongsTo(LocationBlockFloorRoom::class, 'location_block_floor_room_id');
    }
}
