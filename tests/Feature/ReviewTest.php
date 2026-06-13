<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $seller = SellerProfile::factory()->create();

        return Product::factory()->create(['seller_id' => $seller->id]);
    }

    public function test_un_visiteur_ne_peut_pas_deposer_un_avis(): void
    {
        $product = $this->product();
        $this->postJson("/api/products/{$product->id}/reviews", ['rating' => 5])->assertUnauthorized();
    }

    public function test_un_client_connecte_depose_un_avis(): void
    {
        $product = $this->product();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/products/{$product->id}/reviews", [
            'rating' => 4,
            'title' => 'Bon produit',
            'content' => 'Conforme.',
        ])->assertCreated()->assertJsonPath('data.rating', 4);

        $this->assertDatabaseHas('product_reviews', ['product_id' => $product->id, 'rating' => 4]);
    }

    public function test_un_seul_avis_par_produit(): void
    {
        $product = $this->product();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/products/{$product->id}/reviews", ['rating' => 5]);
        $this->postJson("/api/products/{$product->id}/reviews", ['rating' => 3])->assertStatus(422);
    }

    public function test_la_note_du_produit_est_recalculee(): void
    {
        $product = $this->product();
        Sanctum::actingAs(User::factory()->create());

        $this->postJson("/api/products/{$product->id}/reviews", ['rating' => 2]);

        $this->assertEquals(2.0, (float) $product->fresh()->rating);
        $this->assertEquals(1, $product->fresh()->rating_count);
    }
}
