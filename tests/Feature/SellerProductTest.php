<?php

namespace Tests\Feature;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SellerProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_client_ne_peut_pas_creer_de_produit(): void
    {
        Sanctum::actingAs(User::factory()->create()); // buyer

        $this->postJson('/api/seller/products', ['title' => 'X', 'cost_price' => 10])
            ->assertForbidden();
    }

    public function test_un_vendeur_cree_un_produit_avec_prix_calcule(): void
    {
        $user = User::factory()->seller()->create();
        SellerProfile::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/seller/products', [
            'title' => 'Mon produit',
            'cost_price' => 10,
            'markup_coefficient' => 2,
            'markup_fixed' => 1,
            'stock_platform' => 5,
        ])->assertCreated()->assertJsonPath('data.price', 21);

        $this->assertDatabaseHas('products', ['title' => 'Mon produit', 'final_price_calculated' => 21]);
    }
}
