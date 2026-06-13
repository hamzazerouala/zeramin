<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderRefundedMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Services\OrderService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;

class WebhookController extends Controller
{
    public function __construct(
        private StripeService $stripe,
        private OrderService $orders,
    ) {
    }

    /**
     * Endpoint webhook Stripe. Répond 200 rapidement après traitement léger.
     */
    public function stripe(Request $request): JsonResponse
    {
        try {
            $event = $this->stripe->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
            );
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'Signature invalide.'], 400);
        } catch (\UnexpectedValueException $e) {
            return response()->json(['error' => 'Payload invalide.'], 400);
        }

        match ($event->type) {
            'payment_intent.succeeded' => $this->handleSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handleFailed($event->data->object),
            'charge.refunded' => $this->handleRefunded($event->data->object),
            default => Log::info('Webhook Stripe non géré', ['type' => $event->type]),
        };

        return response()->json(['received' => true]);
    }

    private function handleSucceeded(object $intent): void
    {
        PaymentIntent::where('stripe_payment_intent_id', $intent->id)
            ->update(['status' => 'succeeded', 'charge_id' => $intent->latest_charge ?? null]);

        $cart = Cart::where('payment_intent_id', $intent->id)->first();
        if ($cart) {
            $method = $intent->payment_method_types[0] ?? null;
            $this->orders->createFromCart($cart, $intent->id, $method);
        }
    }

    private function handleFailed(object $intent): void
    {
        PaymentIntent::where('stripe_payment_intent_id', $intent->id)->update([
            'status' => 'failed',
            'error_message' => $intent->last_payment_error->message ?? 'Échec du paiement.',
        ]);
        Log::warning('Paiement échoué', ['payment_intent' => $intent->id]);
    }

    private function handleRefunded(object $charge): void
    {
        $paymentIntentId = $charge->payment_intent ?? null;
        if (! $paymentIntentId) {
            return;
        }

        $orders = Order::where('payment_intent_id', $paymentIntentId)->get();
        foreach ($orders as $order) {
            $order->update(['status' => 'refunded', 'payment_status' => 'refunded']);
            try {
                Mail::to($order->customer?->email ?? $order->email_guest)
                    ->send(new OrderRefundedMail($order, 'Remboursement traité.'));
            } catch (\Throwable $e) {
                Log::warning('Email remboursement échoué', ['order' => $order->id]);
            }
        }
    }
}
