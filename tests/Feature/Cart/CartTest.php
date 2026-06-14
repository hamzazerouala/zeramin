<?php

namespace Tests\Feature\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\PromoCode;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function buyerWithCart(): array
    {
        $user = User::factory()->create();
        $cart = Cart::create(['customer_id' => $user->id, 'total_amount' => 0, 'currency' => 'EUR']);

        return [$user, $cart];
    }

    private function activeProduct(): Product
    {
        $seller    = SellerProfile::factory()->create();
        $category  = Category::factory()->create();

        return Product::factory()->create([
            'seller_id'   => $seller->id,
            'category_id' => $category->id,
            'is_active'   => true,
            'stock_platform' => 100,
        ]);
    }

    public function test_ajouter_article_au_panier(): void
    {
        [$user, $cart] = $this->buyerWithCart();
        $product = $this->activeProduct();

        $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity'   => 2,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('cart_items', [
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);
    }

    public function test_modifier_quantite_article(): void
    {
        [$user, $cart] = $this->buyerWithCart();
        $product = $this->activeProduct();
        $item = CartItem::create([
            'cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->putJson("/api/cart/items/{$item->id}", ['quantity' => 5])
            ->assertOk();

        $this->assertEquals(5, $item->fresh()->quantity);
    }

    public function test_supprimer_article_du_panier(): void
    {
        [$user, $cart] = $this->buyerWithCart();
        $product = $this->activeProduct();
        $item = CartItem::create([
            'cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/cart/items/{$item->id}")
            ->assertOk();

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_appliquer_code_promo_valide(): void
    {
        [$user, $cart] = $this->buyerWithCart();
        PromoCode::create([
            'code'           => 'BIENVENUE10',
            'discount_type'  => 'percentage',
            'discount_value' => 10,
            'is_active'      => true,
            'starts_at'      => now()->subDay(),
            'expires_at'     => now()->addDay(),
        ]);

        $this->actingAs($user)
            ->postJson('/api/cart/apply-coupon', ['code' => 'BIENVENUE10'])
            ->assertOk();

        $this->assertEquals('BIENVENUE10', $cart->fresh()->coupon_code);
    }

    public function test_code_promo_invalide_rejete(): void
    {
        [$user] = $this->buyerWithCart();

        $this->actingAs($user)
            ->postJson('/api/cart/apply-coupon', ['code' => 'INVALIDE'])
            ->assertStatus(422);
    }

    public function test_retirer_code_promo(): void
    {
        [$user, $cart] = $this->buyerWithCart();
        $cart->update(['coupon_code' => 'BIENVENUE10']);

        $this->actingAs($user)
            ->deleteJson('/api/cart/coupon')
            ->assertOk();

        $this->assertNull($cart->fresh()->coupon_code);
    }

    public function test_stock_insuffisant_rejete(): void
    {
        [$user] = $this->buyerWithCart();
        $product = $this->activeProduct();
        // Mettre stock à 0
        $product->update(['stock_platform' => 0]);

        $this->actingAs($user)
            ->postJson('/api/cart/items', [
                'product_id' => $product->id,
                'quantity'   => 1,
            ])
            ->assertStatus(422);
    }
}
