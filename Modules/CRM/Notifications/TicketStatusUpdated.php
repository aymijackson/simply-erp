<?php

namespace Modules\CRM\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketStatusUpdated extends Notification
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
            ->subject('Support Ticket Status Updated')
            ->greeting('Hello ' . $notifiable->name)
            ->line('The status of your ticket #' . $this->ticket->ticket_number . ' has been updated.')
            ->line('New Status: ' . ucfirst($this->ticket->status))
            ->action('View Ticket', url('/admin/crm/tickets/' . $this->ticket->id))
            ->line('Thank you for your patience.');
    }
}
