<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesCart;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Cart;
use App\Models\PaymentIntent;
use App\Services\OrderService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ResolvesCart;

    public function __construct(
        private StripeService $stripe,
        private OrderService $orders,
    ) {
    }

    /**
     * Confirme l'état d'un paiement et crée la/les commande(s) si réussi.
     * La création est idempotente (le webhook peut aussi la déclencher).
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate(['payment_intent_id' => ['required', 'string']]);

        $intent = $this->stripe->retrievePaymentIntent($request->payment_intent_id);

        // Met à jour le suivi local du PaymentIntent.
        PaymentIntent::where('stripe_payment_intent_id', $intent->id)
            ->update(['status' => $intent->status]);

        if ($intent->status === 'requires_action') {
            return response()->json([
                'status' => 'requires_action',
                'client_secret' => $intent->client_secret,
            ]);
        }

        if ($intent->status !== 'succeeded') {
            return response()->json([
                'status' => $intent->status,
                'message' => 'Paiement non finalisé.',
            ], 402);
        }

        $cart = Cart::where('payment_intent_id', $intent->id)->first();
        if (! $cart) {
            // Déjà traité (webhook) : renvoyer les commandes existantes.
            $orders = \App\Models\Order::where('payment_intent_id', $intent->id)->with('items')->get();

            return response()->json([
                'status' => 'succeeded',
                'orders' => OrderResource::collection($orders),
            ]);
        }

        $method = $intent->payment_method_types[0] ?? null;
        $orders = $this->orders->createFromCart($cart, $intent->id, $method);

        return response()->json([
            'status' => 'succeeded',
            'orders' => OrderResource::collection($orders->load('items')),
        ], 201);
    }
}
