<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationBlockFloor extends Model
{
    protected $fillable = [
        'name',
        'location_block_id',
    ];
    /**
     * Get the location block that owns the location block floor.
     */
    public function block()
    {
        return $this->belongsTo(LocationBlock::class, 'location_block_id');
    }
}
