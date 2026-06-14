<?php

namespace Tests\Feature\Checkout;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function buyerWithCartAndItem(): array
    {
        $user    = User::factory()->create();
        $cart    = Cart::create(['customer_id' => $user->id, 'total_amount' => 0, 'currency' => 'EUR']);
        $seller  = SellerProfile::factory()->create();
        $cat     = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id'      => $seller->id,
            'category_id'    => $cat->id,
            'is_active'      => true,
            'stock_platform' => 50,
        ]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        return [$user, $cart, $product];
    }

    public function test_calculer_frais_de_port(): void
    {
        [$user, $cart] = $this->buyerWithCartAndItem();

        $this->actingAs($user)
            ->postJson('/api/checkout/calculate-shipping', [
                'shipping_address' => [
                    'country' => 'FR',
                ],
            ])
            ->assertOk()
            ->assertJsonStructure(['subtotal', 'shipping', 'total']);
    }

    public function test_creer_payment_intent(): void
    {
        [$user, $cart] = $this->buyerWithCartAndItem();

        // Le SDK Stripe (cURL) n'est pas interceptable par Http::fake() : on mocke
        // directement la création du PaymentIntent.
        $this->partialMock(StripeService::class, function ($mock) {
            $mock->shouldReceive('createPaymentIntent')->andReturn(\Stripe\PaymentIntent::constructFrom([
                'id'            => 'pi_fake_123',
                'client_secret' => 'cs_fake_123',
                'status'        => 'requires_payment_method',
            ]));
        });

        $this->actingAs($user)
            ->postJson('/api/checkout/create-payment-intent', [
                'shipping_address' => [
                    'country'         => 'FR',
                    'recipient_name'  => 'Jean Dupont',
                    'address'         => '1 rue de la Paix',
                    'city'            => 'Paris',
                    'postal_code'     => '75001',
                ],
            ])
            ->assertOk()
            ->assertJsonStructure(['client_secret', 'payment_intent_id', 'currency', 'breakdown']);
    }

    public function test_panier_vide_echoue_checkout(): void
    {
        $user = User::factory()->create();
        Cart::create(['customer_id' => $user->id, 'total_amount' => 0, 'currency' => 'EUR']);

        $this->actingAs($user)
            ->postJson('/api/checkout/calculate-shipping', [
                'shipping_address' => ['country' => 'FR'],
            ])
            ->assertStatus(422);
    }
}
