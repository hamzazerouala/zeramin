<?php

namespace Tests\Feature\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_peut_voir_ses_commandes(): void
    {
        $user = User::factory()->create();
        Order::factory()->count(3)->create(['customer_id' => $user->id]);

        $this->actingAs($user)
            ->getJson('/api/orders')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_client_peut_voir_une_commande(): void
    {
        $user  = User::factory()->create();
        $order = Order::factory()->create(['customer_id' => $user->id]);

        $this->actingAs($user)
            ->getJson("/api/orders/{$order->id}")
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_client_ne_voit_pas_commande_autre(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $order = Order::factory()->create(['customer_id' => $user2->id]);

        $this->actingAs($user1)
            ->getJson("/api/orders/{$order->id}")
            ->assertForbidden();
    }

    public function test_non_auth_rejete(): void
    {
        $this->getJson('/api/orders')->assertUnauthorized();
    }
}
