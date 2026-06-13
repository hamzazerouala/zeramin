<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderRefundedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public string $reason = '')
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Remboursement de votre commande '.$this->order->order_number);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order_refunded',
            with: ['order' => $this->order, 'reason' => $this->reason],
        );
    }
}
