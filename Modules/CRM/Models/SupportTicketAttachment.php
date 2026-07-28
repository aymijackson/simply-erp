<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketAttachment extends Model
{
    protected $table = 'support_ticket_attachments';

    protected $fillable = [
        'support_ticket_id',
        'comment_id',
        'uploaded_by',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function comment()
    {
        return $this->belongsTo(SupportTicketComment::class, 'comment_id');
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    protected static function booted()
    {
        static::creating(function ($m) {
            if (empty($m->uploaded_by) && auth()->check()) {
                $m->uploaded_by = auth()->id();
            }
        });
    }
}
