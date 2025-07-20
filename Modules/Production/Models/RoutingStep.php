<?php
namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class RoutingStep extends Model
{
    protected $fillable = [
        'routing_id', 'step_number', 'name', 'description', 'duration_minutes'
    ];

    public function routing()
    {
        return $this->belongsTo(Routing::class);
    }
}
