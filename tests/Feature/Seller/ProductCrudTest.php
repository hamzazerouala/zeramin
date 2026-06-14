<?php

namespace Tests\Feature\Seller;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    private function sellerWithProfile(): array
    {
        $user    = User::factory()->seller()->create();
        $profile = SellerProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    public function test_vendeur_peut_creer_un_produit(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/seller/products', [
                'title'              => 'Casque Bluetooth',
                'description'        => 'Un super casque audio.',
                'cost_price'         => 25.00,
                'cost_currency'      => 'EUR',
                'markup_coefficient' => 2.0,
                'markup_fixed'       => 5.0,
                'category_id'        => $category->id,
                'stock_platform'     => 50,
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Casque Bluetooth');

        $this->assertDatabaseHas('products', [
            'seller_id' => $profile->id,
            'title'     => 'Casque Bluetooth',
        ]);
    }

    public function test_vendeur_peut_modifier_son_produit(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        $product = Product::factory()->create(['seller_id' => $profile->id]);

        $this->actingAs($user)
            ->putJson("/api/seller/products/{$product->id}", [
                'title'              => 'Nouveau titre',
                'description'        => $product->description,
                'cost_price'         => $product->cost_price,
                'cost_currency'      => 'EUR',
                'markup_coefficient' => $product->markup_coefficient,
                'markup_fixed'       => $product->markup_fixed,
                'category_id'        => $product->category_id,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Nouveau titre');
    }

    public function test_vendeur_peut_archiver_son_produit(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        $product = Product::factory()->create(['seller_id' => $profile->id, 'is_active' => true]);

        $this->actingAs($user)
            ->deleteJson("/api/seller/products/{$product->id}")
            ->assertOk();

        $this->assertFalse($product->fresh()->is_active);
    }

    public function test_vendeur_ne_peut_pas_modifier_produit_autre_vendeur(): void
    {
        [$user1, $profile1] = $this->sellerWithProfile();
        [$user2, $profile2] = $this->sellerWithProfile();
        $product = Product::factory()->create(['seller_id' => $profile2->id]);

        $this->actingAs($user1)
            ->putJson("/api/seller/products/{$product->id}", [
                'title' => 'Piratage',
            ])
            ->assertForbidden();
    }

    public function test_vendeur_import_aliexpress_mock(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        $category = Category::factory()->create();

        // Mock du scraper AliExpress
        Http::fake([
            '*' => Http::response('<html><script type="application/ld+json">{"@type":"Product","name":"Écouteurs TWS","offers":{"price":12.99,"priceCurrency":"USD"}}</script></html>', 200),
        ]);

        $this->actingAs($user)
            ->postJson('/api/seller/products/import-aliexpress', [
                'url'         => 'https://www.aliexpress.com/item/1234567890.html',
                'category_id' => $category->id,
            ])
            ->assertCreated();
    }

    public function test_acheteur_ne_peut_pas_creer_produit(): void
    {
        $buyer = User::factory()->create(['user_type' => 'buyer']);
        $category = Category::factory()->create();

        $this->actingAs($buyer)
            ->postJson('/api/seller/products', [
                'title'       => 'Test',
                'category_id' => $category->id,
            ])
            ->assertForbidden();
    }
}
