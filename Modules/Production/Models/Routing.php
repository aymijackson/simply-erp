<?php

namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class Routing extends Model
{
    protected $fillable = [
        'name', 'description', 'is_active'
    ];

    public function steps()
    {
        return $this->hasMany(RoutingStep::class);
    }
}
