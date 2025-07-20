<?php

namespace Modules\CRM\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketCreated extends Notification
{
    protected $ticket;

    public function __construct($ticket)
    {
        $this->ticket = $ticket;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Support Ticket Created')
            ->greeting('Hello ' . $notifiable->name)
            ->line('A new support ticket has been created with ID: #' . $this->ticket->ticket_number)
            ->line('Subject: ' . $this->ticket->subject)
            ->action('View Ticket', url('/admin/crm/tickets/' . $this->ticket->id))
            ->line('Thank you for using our support service!');
    }
}
