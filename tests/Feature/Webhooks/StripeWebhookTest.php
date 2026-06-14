<?php

namespace Tests\Feature\Webhooks;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\PaymentIntent;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_signature_invalide_rejete(): void
    {
        $this->postJson('/api/webhooks/stripe', [
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_fake']],
        ], ['Stripe-Signature' => 'invalid_sig'])
            ->assertStatus(400);
    }

    public function test_payment_intent_succeeded_cree_commande(): void
    {
        // Ce test vérifie le flux complet : on mock StripeService pour valider la signature
        // puis on vérifie que le PaymentIntent passe à succeeded
        $user    = User::factory()->create();
        $seller  = SellerProfile::factory()->create();
        $cat     = Category::factory()->create();
        $product = Product::factory()->create([
            'seller_id'      => $seller->id,
            'category_id'    => $cat->id,
            'is_active'      => true,
            'stock_platform' => 50,
        ]);

        $cart = Cart::create([
            'customer_id' => $user->id,
            'total_amount' => $product->final_price_calculated,
            'currency' => 'EUR',
            'payment_intent_id' => 'pi_test_123',
        ]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        PaymentIntent::create([
            'stripe_payment_intent_id' => 'pi_test_123',
            'stripe_client_secret'     => 'cs_test',
            'amount'    => (int) round($product->final_price_calculated * 100),
            'currency'  => 'EUR',
            'status'    => 'requires_payment_method',
        ]);

        // On mock le StripeService pour contourner la vérification de signature
        $this->mock(\App\Services\StripeService::class, function ($mock) {
            $event = \Stripe\Event::constructFrom([
                'id' => 'evt_test',
                'type' => 'payment_intent.succeeded',
                'data' => [
                    'object' => [
                        'id' => 'pi_test_123',
                        'latest_charge' => 'ch_test_123',
                        'payment_method_types' => ['card'],
                    ],
                ],
            ]);

            $mock->shouldReceive('constructWebhookEvent')->andReturn($event);
        });

        $this->postJson('/api/webhooks/stripe', [], ['Stripe-Signature' => 'test_sig'])
            ->assertOk()
            ->assertJson(['received' => true]);

        // Vérifier que le PaymentIntent a été mis à jour
        $this->assertEquals('succeeded', PaymentIntent::first()->status);

        // Vérifier qu'une commande a été créée
        $this->assertDatabaseHas('orders', [
            'customer_id'      => $user->id,
            'payment_intent_id' => 'pi_test_123',
        ]);
    }
}
