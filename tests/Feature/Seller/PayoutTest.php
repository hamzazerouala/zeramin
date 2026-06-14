<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payout;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutTest extends TestCase
{
    use RefreshDatabase;

    private function sellerWithProfile(): array
    {
        $user    = User::factory()->seller()->create();
        $profile = SellerProfile::factory()->create(['user_id' => $user->id]);
        return [$user, $profile];
    }

    public function test_vendeur_peut_voir_son_historique_virements(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        Payout::factory()->count(3)->create(['seller_id' => $profile->id]);

        $this->actingAs($user)->getJson('/api/seller/payouts')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_vendeur_sans_commandes_ne_peut_pas_demander_virement(): void
    {
        [$user] = $this->sellerWithProfile();

        $this->actingAs($user)->postJson('/api/seller/payouts/request')
            ->assertStatus(422);
    }

    public function test_vendeur_avec_commandes_peut_demander_virement(): void
    {
        [$user, $profile] = $this->sellerWithProfile();

        // Créer une commande livrée
        Order::factory()->create([
            'seller_id'      => $profile->id,
            'status'         => 'delivered',
            'payment_status' => 'succeeded',
            'total_amount'   => 100.00,
        ]);

        $this->actingAs($user)->postJson('/api/seller/payouts/request')
            ->assertCreated()
            ->assertJsonStructure(['id', 'net_amount', 'status']);
    }

    public function test_acheteur_ne_peut_pas_acceder_aux_virements(): void
    {
        $buyer = User::factory()->create(['user_type' => 'buyer']);

        $this->actingAs($buyer)->getJson('/api/seller/payouts')
            ->assertForbidden();
    }
}
