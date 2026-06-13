<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesCart;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\PaymentIntent;
use App\Models\PromoCode;
use App\Services\ShippingService;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    use ResolvesCart;

    public function __construct(
        private ShippingService $shipping,
        private StripeService $stripe,
    ) {
    }

    /** Calcule les frais de port pour l'adresse fournie. */
    public function calculateShipping(Request $request): JsonResponse
    {
        $request->validate([
            'shipping_address' => ['required', 'array'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
        ]);

        $cart = $this->loadedCart($request);
        $this->ensureNotEmpty($cart);

        $breakdown = $this->computeTotals($cart, strtoupper($request->input('shipping_address.country')));

        return response()->json($breakdown);
    }

    /** Crée le PaymentIntent Stripe (sans encore créer la commande). */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $request->validate([
            'shipping_address' => ['required', 'array'],
            'shipping_address.country' => ['required', 'string', 'size:2'],
            'shipping_address.recipient_name' => ['required', 'string'],
            'shipping_address.address' => ['required', 'string'],
            'shipping_address.city' => ['required', 'string'],
            'shipping_address.postal_code' => ['required', 'string'],
            'customer_email' => ['nullable', 'email'],
        ]);

        $cart = $this->loadedCart($request);
        $this->ensureNotEmpty($cart);

        $country = strtoupper($request->input('shipping_address.country'));

        // Validation des stocks
        foreach ($cart->items as $item) {
            if ($item->product->stock_platform < $item->quantity) {
                throw ValidationException::withMessages([
                    'cart' => ["Stock insuffisant pour « {$item->product->title} »."],
                ]);
            }
        }

        $totals = $this->computeTotals($cart, $country);

        $currency = $this->stripe->currencyForCountry($country);
        $paymentMethods = $this->stripe->paymentMethodsForCountry($country);
        $amountCents = (int) round($totals['total'] * 100);

        $sellerIds = $cart->items->pluck('product.seller_id')->unique()->values()->all();

        $intent = $this->stripe->createPaymentIntent($amountCents, $currency, [
            'cart_id' => (string) $cart->id,
            'seller_ids' => implode(',', $sellerIds),
            'customer_email' => $request->input('customer_email', $request->user()?->email),
        ]);

        $cart->update([
            'payment_intent_id' => $intent->id,
            'shipping_address' => $request->input('shipping_address'),
            'total_amount' => $totals['total'],
            'currency' => strtoupper($currency),
        ]);

        PaymentIntent::updateOrCreate(
            ['stripe_payment_intent_id' => $intent->id],
            [
                'stripe_client_secret' => $intent->client_secret,
                'amount' => $amountCents,
                'currency' => strtoupper($currency),
                'status' => $intent->status,
            ],
        );

        return response()->json([
            'client_secret' => $intent->client_secret,
            'payment_intent_id' => $intent->id,
            'payment_methods' => $paymentMethods,
            'currency' => strtoupper($currency),
            'breakdown' => $totals,
        ]);
    }

    /**
     * Sous-total, remise, port (somme par vendeur), total.
     *
     * @return array{subtotal:float,discount:float,shipping:float,tax:float,total:float}
     */
    private function computeTotals(Cart $cart, string $country): array
    {
        $subtotal = $cart->subtotal();
        $discount = $this->couponDiscount($cart, $subtotal);

        // Frais de port cumulés par vendeur distinct présent dans le panier.
        $shipping = 0.0;
        foreach ($cart->items->groupBy('product.seller_id') as $items) {
            $seller = $items->first()->product->seller;
            if ($seller) {
                $shipping += $this->shipping->calculate($seller, $country, $items);
            }
        }

        $tax = 0.0; // Hors scope Phase 1
        $total = round(max(0, $subtotal - $discount) + $shipping + $tax, 2);

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'shipping' => round($shipping, 2),
            'tax' => $tax,
            'total' => $total,
        ];
    }

    private function couponDiscount(Cart $cart, float $subtotal): float
    {
        if (! $cart->coupon_code) {
            return 0.0;
        }

        $promo = PromoCode::where('code', $cart->coupon_code)->first();
        if (! $promo || ! $promo->isValid()) {
            return 0.0;
        }
        if ($promo->min_order_value && $subtotal < (float) $promo->min_order_value) {
            return 0.0;
        }

        return $promo->discount_type === 'percentage'
            ? round($subtotal * ((float) $promo->discount_value / 100), 2)
            : min((float) $promo->discount_value, $subtotal);
    }

    private function loadedCart(Request $request): Cart
    {
        $cart = $this->resolveCart($request, create: false);
        if (! $cart) {
            throw ValidationException::withMessages(['cart' => ['Panier introuvable.']]);
        }

        return $cart->load(['items.product.seller', 'items.product.images', 'items.variant']);
    }

    private function ensureNotEmpty(Cart $cart): void
    {
        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => ['Le panier est vide.']]);
        }
    }
}
