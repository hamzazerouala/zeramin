<?php

namespace App\Jobs;

use App\Mail\OrderRefundedMail;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Services\AliExpressOrderService;
use App\Services\StripeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CreateAliExpressOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 600, 3600];

    public function __construct(public Order $order)
    {
    }

    public function handle(AliExpressOrderService $service): void
    {
        $tracking = $service->placeOrder($this->order);

        // Fulfillment automatique réussi : passage en "shipped".
        if ($tracking) {
            $this->order->update([
                'aliexpress_order_number' => $tracking,
                'aliexpress_tracking_id' => $tracking,
                'status' => 'shipped',
            ]);

            $this->notify(new OrderShippedMail($this->order->fresh()));
        }
        // Sinon (mode manuel) : la commande reste "processing".
    }

    /**
     * Après épuisement des tentatives : rembourser et notifier.
     */
    public function failed(Throwable $e): void
    {
        Log::error('Création commande AliExpress définitivement échouée', [
            'order_id' => $this->order->id,
            'error' => $e->getMessage(),
        ]);

        try {
            if ($this->order->payment_intent_id) {
                app(StripeService::class)->refund($this->order->payment_intent_id);
            }
        } catch (Throwable $refundError) {
            Log::error('Remboursement automatique échoué', [
                'order_id' => $this->order->id,
                'error' => $refundError->getMessage(),
            ]);
        }

        $this->order->update(['status' => 'refunded', 'payment_status' => 'refunded']);
        $this->notify(new OrderRefundedMail($this->order->fresh(), 'Problème technique lors du traitement.'));
    }

    private function notify($mailable): void
    {
        $to = $this->order->customer?->email ?? $this->order->email_guest;
        if (! $to) {
            return;
        }
        try {
            Mail::to($to)->send($mailable);
        } catch (Throwable $e) {
            Log::warning('Notification commande échouée', ['order_id' => $this->order->id]);
        }
    }
}
