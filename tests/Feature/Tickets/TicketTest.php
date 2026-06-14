<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function buyer(): User
    {
        return User::factory()->create(['user_type' => 'buyer']);
    }

    private function admin(): User
    {
        return User::factory()->create(['user_type' => 'admin']);
    }

    public function test_client_peut_creer_un_ticket(): void
    {
        $user = $this->buyer();

        $this->actingAs($user)->postJson('/api/tickets', [
            'subject' => 'Problème de livraison',
            'message' => 'Ma commande n\'est pas arrivée depuis 15 jours.',
        ])->assertCreated()
            ->assertJsonStructure(['id', 'subject', 'status']);

        $this->assertDatabaseHas('support_tickets', [
            'user_id' => $user->id,
            'subject' => 'Problème de livraison',
            'status'  => 'open',
        ]);
    }

    public function test_client_peut_lister_ses_tickets(): void
    {
        $user = $this->buyer();
        SupportTicket::factory()->count(3)->create(['user_id' => $user->id]);

        $this->actingAs($user)->getJson('/api/tickets')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_client_ne_voit_pas_les_tickets_des_autres(): void
    {
        $user1 = $this->buyer();
        $user2 = $this->buyer();
        $ticket = SupportTicket::factory()->create(['user_id' => $user2->id]);

        $this->actingAs($user1)->getJson("/api/tickets/{$ticket->id}")
            ->assertForbidden();
    }

    public function test_client_peut_ajouter_un_message(): void
    {
        $user   = $this->buyer();
        $ticket = SupportTicket::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->postJson("/api/tickets/{$ticket->id}/messages", [
            'message' => 'Voici des informations supplémentaires.',
        ])->assertCreated()
            ->assertJsonStructure(['id', 'message']);
    }

    public function test_admin_voit_tous_les_tickets(): void
    {
        $admin = $this->admin();
        SupportTicket::factory()->count(5)->create();

        $this->actingAs($admin)->getJson('/api/admin/tickets')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_admin_peut_changer_le_statut(): void
    {
        $admin  = $this->admin();
        $ticket = SupportTicket::factory()->create(['status' => 'open']);

        $this->actingAs($admin)->putJson("/api/admin/tickets/{$ticket->id}/status", [
            'status' => 'resolved',
        ])->assertOk();

        $this->assertDatabaseHas('support_tickets', [
            'id'     => $ticket->id,
            'status' => 'resolved',
        ]);
    }

    public function test_non_authentifie_ne_peut_pas_creer_un_ticket(): void
    {
        $this->postJson('/api/tickets', [
            'subject' => 'Test',
            'message' => 'Test message',
        ])->assertUnauthorized();
    }
}
