<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Order $order,
        public readonly string $previousStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $statusLabel = match ($this->order->status) {
            'processing'  => 'en cours de traitement',
            'shipped'     => 'expédiée',
            'in_transit'  => 'en transit',
            'delivered'   => 'livrée',
            'cancelled'   => 'annulée',
            'disputed'    => 'en litige',
            default       => $this->order->status,
        };

        $url = config('app.url').'/account/orders/'.$this->order->id;

        return (new MailMessage)
            ->subject('Votre commande '.$this->order->order_number.' est '.$statusLabel)
            ->greeting('Bonjour '.($notifiable->name ?? '').',')
            ->line('Votre commande **'.$this->order->order_number.'** est maintenant '.$statusLabel.'.')
            ->when($this->order->status === 'shipped' && $this->order->tracking?->tracking_id, function ($mail) {
                return $mail->line('Numéro de suivi : '.$this->order->tracking['tracking_id'])
                    ->when($this->order->tracking['tracking_url'] ?? null, fn ($m) =>
                        $m->action('Suivre ma commande', $this->order->tracking['tracking_url'])
                    );
            })
            ->action('Voir ma commande', $url)
            ->line('Merci de faire confiance à DropShop.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'       => 'order_status',
            'order_id'   => $this->order->id,
            'order_number' => $this->order->order_number,
            'status'     => $this->order->status,
            'url'        => '/account/orders/'.$this->order->id,
        ];
    }
}
