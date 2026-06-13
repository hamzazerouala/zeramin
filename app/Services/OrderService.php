<?php

namespace App\Services;

use App\Jobs\CreateAliExpressOrderJob;
use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\PaymentIntent;
use App\Models\PromoCode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    public function __construct(private ShippingService $shipping)
    {
    }

    /**
     * Crée une commande par vendeur à partir du panier payé. Idempotent :
     * si des commandes existent déjà pour ce PaymentIntent, elles sont renvoyées.
     *
     * @return Collection<int,Order>
     */
    public function createFromCart(Cart $cart, string $paymentIntentId, string $paymentMethod = null): Collection
    {
        $existing = Order::where('payment_intent_id', $paymentIntentId)->get();
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $cart->loadMissing(['items.product.seller', 'items.variant']);
        $country = strtoupper($cart->shipping_address['country'] ?? 'WORLD');
        $currency = $cart->currency ?? config('aliexpress.store_currency', 'EUR');

        $orders = DB::transaction(function () use ($cart, $paymentIntentId, $paymentMethod, $country, $currency) {
            $created = new Collection();

            foreach ($cart->items->groupBy('product.seller_id') as $sellerId => $items) {
                $seller = $items->first()->product->seller;
                if (! $seller) {
                    continue;
                }

                $subtotal = (float) $items->sum(fn ($i) => (float) $i->product->display_price * $i->quantity);
                $discount = $this->sellerDiscount($cart, $seller->id, $subtotal);
                $shippingCost = $this->shipping->calculate($seller, $country, $items);
                $total = round(max(0, $subtotal - $discount) + $shippingCost, 2);

                $order = Order::create([
                    'order_number' => Order::generateOrderNumber($this->prefixForCountry($country)),
                    'seller_id' => $seller->id,
                    'customer_id' => $cart->customer_id,
                    'email_guest' => $cart->customer_id ? null : ($cart->shipping_address['email'] ?? null),
                    'subtotal' => round($subtotal, 2),
                    'shipping_cost' => round($shippingCost, 2),
                    'tax_amount' => 0,
                    'total_amount' => $total,
                    'currency' => $currency,
                    'status' => 'processing',
                    'payment_intent_id' => $paymentIntentId,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'succeeded',
                    'shipping_address' => $cart->shipping_address,
                    'billing_address' => $cart->shipping_address,
                ]);

                foreach ($items as $item) {
                    $unit = (float) $item->product->display_price;
                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $unit,
                        'subtotal' => round($unit * $item->quantity, 2),
                        'aliexpress_product_id' => $item->product->aliexpress_product_id,
                        'aliexpress_sku_id' => $item->variant?->aliexpress_sku_id,
                        'title_snapshot' => $item->product->title,
                    ]);

                    // Décrémente le stock plateforme.
                    $item->product->decrement('stock_platform', $item->quantity);
                }

                $seller->increment('total_sales');
                $created->push($order);
            }

            // Lie le PaymentIntent à la première commande + incrémente le coupon.
            if ($first = $created->first()) {
                PaymentIntent::where('stripe_payment_intent_id', $paymentIntentId)
                    ->update(['order_id' => $first->id, 'status' => 'succeeded', 'payment_method_used' => $paymentMethod]);
            }
            if ($cart->coupon_code) {
                PromoCode::where('code', $cart->coupon_code)->increment('uses_count');
            }

            return $created;
        });

        // Effets de bord hors transaction.
        foreach ($orders as $order) {
            CreateAliExpressOrderJob::dispatch($order);
            try {
                Mail::to($order->customer?->email ?? $order->email_guest)->send(new OrderConfirmationMail($order));
            } catch (\Throwable $e) {
                Log::warning('Envoi email confirmation échoué', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }

        // Vide le panier.
        $cart->items()->delete();
        $cart->delete();

        return $orders;
    }

    private function sellerDiscount(Cart $cart, int $sellerId, float $subtotal): float
    {
        if (! $cart->coupon_code) {
            return 0.0;
        }
        $promo = PromoCode::where('code', $cart->coupon_code)->where('seller_id', $sellerId)->first();
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

    private function prefixForCountry(string $country): string
    {
        return match ($country) {
            'FR' => 'FRA', 'GB' => 'GBR', 'US' => 'USA', 'DE' => 'DEU',
            'ES' => 'ESP', 'IT' => 'ITA', 'BE' => 'BEL', 'NL' => 'NLD',
            default => 'INT',
        };
    }
}
