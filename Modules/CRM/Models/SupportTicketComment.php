<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\HRM\Models\Employee;

class SupportTicketComment extends Model
{
    protected $table = 'support_ticket_comments';

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function author()
    {
        // support_ticket_comments.user_id (users.id) -> employees.user_id
        return $this->belongsTo(Employee::class, 'user_id', 'user_id');
    }

    public function attachments()
    {
        return $this->hasMany(SupportTicketAttachment::class, 'comment_id');
    }

    protected static function booted()
    {
        static::creating(function ($m) {
            if (empty($m->user_id) && auth()->check()) {
                $m->user_id = auth()->id();
            }
        });
    }
}
