<?php

namespace Tests\Feature\Seller;

use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SellerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function sellerWithProfile(): array
    {
        $user    = User::factory()->seller()->create();
        $profile = SellerProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    public function test_vendeur_accede_au_dashboard(): void
    {
        [$user, $profile] = $this->sellerWithProfile();

        $this->actingAs($user)
            ->getJson('/api/seller/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'revenue_month', 'orders_total', 'avg_basket',
                'orders_by_status', 'low_stock_products', 'recent_orders',
            ]);
    }

    public function test_vendeur_accede_aux_analytics(): void
    {
        [$user, $profile] = $this->sellerWithProfile();

        $this->actingAs($user)
            ->getJson('/api/seller/analytics?days=7')
            ->assertOk()
            ->assertJsonStructure([
                'period_days', 'total_revenue', 'total_orders',
                'avg_basket', 'unique_customers', 'sales_by_day', 'top_products',
            ]);
    }

    public function test_vendeur_voit_ses_commandes(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        Order::factory()->count(3)->create(['seller_id' => $profile->id]);

        $this->actingAs($user)
            ->getJson('/api/seller/orders')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_vendeur_filtrer_commandes_par_statut(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        Order::factory()->create(['seller_id' => $profile->id, 'status' => 'delivered']);
        Order::factory()->create(['seller_id' => $profile->id, 'status' => 'pending']);

        $res = $this->actingAs($user)
            ->getJson('/api/seller/orders?status=delivered')
            ->assertOk();

        $this->assertCount(1, $res->json('data'));
    }

    public function test_vendeur_peut_changer_statut_commande(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        $order = Order::factory()->create(['seller_id' => $profile->id, 'status' => 'pending']);

        $this->actingAs($user)
            ->putJson("/api/seller/orders/{$order->id}/status", [
                'status' => 'processing',
            ])
            ->assertOk();

        $this->assertEquals('processing', $order->fresh()->status);
    }

    public function test_vendeur_ne_peut_pas_modifier_commande_autre_boutique(): void
    {
        [$user1, $profile1] = $this->sellerWithProfile();
        [$user2, $profile2] = $this->sellerWithProfile();
        $order = Order::factory()->create(['seller_id' => $profile2->id]);

        $this->actingAs($user1)
            ->putJson("/api/seller/orders/{$order->id}/status", ['status' => 'shipped'])
            ->assertForbidden();
    }

    public function test_acheteur_ne_peut_pas_acceder_dashboard(): void
    {
        $buyer = User::factory()->create(['user_type' => 'buyer']);

        $this->actingAs($buyer)
            ->getJson('/api/seller/dashboard')
            ->assertForbidden();
    }
}
