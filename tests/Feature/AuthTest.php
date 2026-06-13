<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_inscription_client_retourne_un_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Jean Client',
            'email' => 'jean@example.com',
            'password' => 'MotDePasse12!',
            'password_confirmation' => 'MotDePasse12!',
            'user_type' => 'buyer',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['user' => ['id', 'email'], 'token']);

        $this->assertDatabaseHas('users', ['email' => 'jean@example.com', 'user_type' => 'buyer']);
    }

    public function test_inscription_vendeur_cree_le_profil_boutique(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Marie Vendeuse',
            'email' => 'marie@example.com',
            'password' => 'MotDePasse12!',
            'password_confirmation' => 'MotDePasse12!',
            'user_type' => 'seller',
            'shop_name' => 'Boutique Marie',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('seller_profiles', ['shop_slug' => 'boutique-marie']);
    }

    public function test_connexion_echoue_avec_mauvais_identifiants(): void
    {
        User::factory()->create(['email' => 'a@b.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'a@b.com',
            'password' => 'mauvais',
        ])->assertStatus(422);
    }

    public function test_connexion_reussie_retourne_un_token(): void
    {
        $user = User::factory()->create(['email' => 'ok@b.com']);

        $this->postJson('/api/auth/login', [
            'email' => 'ok@b.com',
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_mot_de_passe_faible_est_rejete(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Test',
            'email' => 'weak@b.com',
            'password' => 'faible',
            'password_confirmation' => 'faible',
            'user_type' => 'buyer',
        ])->assertStatus(422);
    }
}
