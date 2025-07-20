<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CRM\Models\SupportTicketAttachment;

class SupportTicket extends Model
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

    protected $dates = ['resolved_at'];

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

    public function attachments()
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }
}
