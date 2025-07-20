<?php

namespace Modules\CRM\App\Mails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class SupportTicketNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $subjectLine;

    public function __construct($ticket, $subjectLine)
    {
        $this->ticket = $ticket;
        $this->subjectLine = $subjectLine;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
                    ->view('crm.emails.support_ticket_notification');
    }
}
