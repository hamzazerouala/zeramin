<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketMessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly TicketMessage $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->message->user?->name ?? 'Support';
        $subject    = 'Nouveau message sur votre ticket : '.$this->ticket->subject;
        $url        = config('app.url').'/account/tickets/'.$this->ticket->id;

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour '.($notifiable->name ?? '').',')
            ->line("{$senderName} a répondu à votre ticket « {$this->ticket->subject} ».")
            ->line('Message : '.mb_substr($this->message->message, 0, 200).(mb_strlen($this->message->message) > 200 ? '…' : ''))
            ->action('Voir le ticket', $url)
            ->line('Merci d\'utiliser notre service de support.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'      => 'ticket_message',
            'ticket_id' => $this->ticket->id,
            'subject'   => $this->ticket->subject,
            'sender'    => $this->message->user?->name ?? 'Support',
            'excerpt'   => mb_substr($this->message->message, 0, 100),
            'url'       => '/account/tickets/'.$this->ticket->id,
        ];
    }
}
