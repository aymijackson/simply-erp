<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    protected $fillable = [
        'subject',
        'details',
        'interaction_type',
        'interaction_date',
        'employee_id',
        'interactable_type',
        'interactable_id',
    ];

    protected $casts = [
        'interaction_date' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(\Modules\HRM\Models\Employee::class);
    }

    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }
}
