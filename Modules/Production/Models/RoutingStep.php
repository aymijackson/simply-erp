<?php
namespace Modules\Production\Models;

use Illuminate\Database\Eloquent\Model;

class RoutingStep extends Model
{
    protected $fillable = [
        'routing_id', 'sequence', 'step_name', 'instructions', 'duration_minutes'
    ];

    public function routing()
    {
        return $this->belongsTo(Routing::class);
    }
}
