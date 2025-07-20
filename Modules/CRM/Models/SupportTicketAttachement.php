<?php
namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class SupportTicketAttachment extends Model
{
    protected $fillable = ['support_ticket_id', 'file_path', 'file_name', 'uploaded_by'];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
