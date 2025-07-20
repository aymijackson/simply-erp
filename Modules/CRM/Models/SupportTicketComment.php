<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketComment extends Model
{

    protected $fillable = [
        'subject',
        'description',
        'priority',
        'status',
        'customer_id',
        'assigned_to',
        'resolved_at'
    ];


    /**
     * Get the customer that raised the ticket.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\Modules\CRM\Models\Customer::class);
    }

    /**
     * Get the employee assigned to this ticket.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(\Modules\HRM\Models\Employee::class, 'assigned_to');
    }
}
