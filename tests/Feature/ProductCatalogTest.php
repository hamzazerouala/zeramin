<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(array $attrs = []): Product
    {
        $seller = SellerProfile::create([
            'user_id' => User::factory()->seller()->create()->id,
            'shop_name' => 'Shop',
            'shop_slug' => 'shop-'.uniqid(),
            'is_active' => true,
        ]);

        return Product::create(array_merge([
            'seller_id' => $seller->id,
            'title' => 'Produit Test',
            'slug' => 'produit-test-'.uniqid(),
            'cost_price' => 10,
            'markup_coefficient' => 2,
            'markup_fixed' => 1,
            'final_price_calculated' => 21,
            'stock_platform' => 5,
            'is_active' => true,
        ], $attrs));
    }

    public function test_le_catalogue_public_liste_les_produits_actifs(): void
    {
        $this->createProduct(['title' => 'Visible']);
        $this->createProduct(['title' => 'Caché', 'is_active' => false]);

        $response = $this->getJson('/api/products');

        $response->assertOk()->assertJsonPath('data.0.title', 'Visible');
        $this->assertCount(1, $response->json('data'));
    }

    public function test_detail_produit_par_slug(): void
    {
        $product = $this->createProduct(['slug' => 'mon-produit-unique']);

        $this->getJson('/api/products/mon-produit-unique')
            ->assertOk()
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_prix_final_calcule(): void
    {
        $product = $this->createProduct();
        // (10 * 2) + 1 = 21
        $this->assertEquals(21.0, $product->computeFinalPrice());
    }
}
