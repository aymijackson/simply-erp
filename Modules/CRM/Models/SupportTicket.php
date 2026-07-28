<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\HRM\Models\Employee;
use Modules\CRM\Models\SupportTicketAttachment;
use Modules\CRM\Models\SupportTicketComment;

class SupportTicket extends Model
{
    protected $fillable = [
        'ticket_no',
        'subject',
        'description',
        'status',        // open, pending, resolved, closed
        'priority',      // low, medium, high, urgent
        'channel',       // web, email, phone, whatsapp, other
        'category',      // billing, technical, account, other
        'customer_id',
        'assigned_to',   // employee_id (nullable)
        'created_by',    // employee_id
    ];
    
    protected static function booted()
    {
        static::creating(function ($t) {
            // leave empty here; we’ll set after we have ID if needed
        });
    
        static::created(function ($t) {
            if (!$t->ticket_no) {
                $t->ticket_no = 'TCK-' . str_pad((string)$t->id, 6, '0', STR_PAD_LEFT);
                $t->saveQuietly();
            }
        });
    }


    public function customer()
    {
        return $this->belongsTo(\Modules\CRM\Models\Customer::class, 'customer_id');
    }

    public function assignee()
    {
        return $this->belongsTo(Employee::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function comments()
    {
        return $this->hasMany(SupportTicketComment::class, 'support_ticket_id')->latest();
    }

    public function attachments()
    {
        return $this->hasMany(SupportTicketAttachment::class, 'support_ticket_id')->latest();
    }
    

}
