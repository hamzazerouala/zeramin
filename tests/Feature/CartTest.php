<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $stock = 5): Product
    {
        $seller = SellerProfile::create([
            'user_id' => User::factory()->seller()->create()->id,
            'shop_name' => 'Shop',
            'shop_slug' => 'shop-'.uniqid(),
            'is_active' => true,
        ]);

        return Product::create([
            'seller_id' => $seller->id,
            'title' => 'Article',
            'slug' => 'article-'.uniqid(),
            'cost_price' => 10,
            'markup_coefficient' => 2,
            'markup_fixed' => 0,
            'final_price_calculated' => 20,
            'stock_platform' => $stock,
            'is_active' => true,
        ]);
    }

    public function test_ajout_au_panier_invite(): void
    {
        $product = $this->product();

        $response = $this->withHeaders(['X-Cart-Token' => 'tok-123'])
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        $response->assertCreated()
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.subtotal', 40);
    }

    public function test_ajout_refuse_si_stock_insuffisant(): void
    {
        $product = $this->product(stock: 1);

        $this->withHeaders(['X-Cart-Token' => 'tok-456'])
            ->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 5])
            ->assertStatus(422);
    }

    public function test_consultation_du_panier(): void
    {
        $product = $this->product();
        $headers = ['X-Cart-Token' => 'tok-789'];

        $this->withHeaders($headers)->postJson('/api/cart/items', ['product_id' => $product->id]);

        $this->withHeaders($headers)->getJson('/api/cart')
            ->assertOk()
            ->assertJsonPath('data.item_count', 1);
    }
}
