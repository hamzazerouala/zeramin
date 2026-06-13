<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_ajout_consultation_suppression_favori(): void
    {
        $product = Product::factory()->create(['seller_id' => SellerProfile::factory()]);
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/wishlist/items', ['product_id' => $product->id])->assertCreated();
        $this->getJson('/api/wishlist')->assertOk()->assertJsonPath('data.0.id', $product->id);

        $this->deleteJson("/api/wishlist/items/{$product->id}")->assertOk();
        $this->getJson('/api/wishlist')->assertOk()->assertJsonCount(0, 'data');
    }
}
