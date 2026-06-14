<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\SellerProfile;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    private function sellerWithProfile(): array
    {
        $user    = User::factory()->seller()->create();
        $profile = SellerProfile::factory()->create(['user_id' => $user->id]);

        return [$user, $profile];
    }

    public function test_admin_peut_lister_les_utilisateurs(): void
    {
        User::factory()->count(5)->create();

        $this->actingAs($this->admin())
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_admin_peut_filtrer_par_type(): void
    {
        User::factory()->count(3)->create(['user_type' => 'buyer']);
        User::factory()->seller()->count(2)->create();

        $res = $this->actingAs($this->admin())
            ->getJson('/api/admin/users?type=seller')
            ->assertOk();

        $this->assertCount(2, $res->json('data'));
    }

    public function test_admin_peut_verifier_kyc_vendeur(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        $this->assertNull($profile->kyc_verified_at);

        $this->actingAs($this->admin())
            ->putJson("/api/admin/users/{$user->id}/verify")
            ->assertOk()
            ->assertJson(['message' => 'Vendeur vérifié.']);

        $this->assertNotNull($profile->fresh()->kyc_verified_at);
    }

    public function test_verifier_kyc_sur_non_vendeur_echoue(): void
    {
        $buyer = User::factory()->create(['user_type' => 'buyer']);

        $this->actingAs($this->admin())
            ->putJson("/api/admin/users/{$buyer->id}/verify")
            ->assertStatus(422);
    }

    public function test_admin_voit_les_litiges(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        Order::factory()->create(['seller_id' => $profile->id, 'status' => 'disputed']);
        Order::factory()->create(['seller_id' => $profile->id, 'status' => 'pending']);

        $res = $this->actingAs($this->admin())
            ->getJson('/api/admin/disputes')
            ->assertOk();

        $this->assertCount(1, $res->json('data'));
    }

    public function test_admin_peut_resoudre_un_litige(): void
    {
        [$user, $profile] = $this->sellerWithProfile();
        $order = Order::factory()->create(['seller_id' => $profile->id, 'status' => 'disputed']);

        $this->actingAs($this->admin())
            ->putJson("/api/admin/disputes/{$order->id}/resolve", [
                'resolution' => 'refunded',
                'note'       => 'Remboursement effectué.',
            ])
            ->assertOk()
            ->assertJson(['status' => 'refunded']);

        $this->assertEquals('refunded', $order->fresh()->status);
    }

    public function test_admin_peut_voir_les_stats(): void
    {
        User::factory()->count(10)->create();
        User::factory()->seller()->count(3)->create();

        $res = $this->actingAs($this->admin())
            ->getJson('/api/admin/stats')
            ->assertOk()
            ->assertJsonStructure(['total_users', 'total_sellers', 'open_disputes', 'open_tickets']);

        $this->assertEquals(13, $res->json('total_users')); // 10 + 3
        $this->assertEquals(3, $res->json('total_sellers'));
    }

    public function test_non_admin_ne_peut_pas_acceder(): void
    {
        $buyer = User::factory()->create(['user_type' => 'buyer']);

        $this->actingAs($buyer)
            ->getJson('/api/admin/users')
            ->assertForbidden();
    }

    public function test_non_authentifie_rejete(): void
    {
        $this->getJson('/api/admin/users')->assertUnauthorized();
    }
}
