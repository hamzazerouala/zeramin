<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_accepte_email_existant(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        $this->postJson('/api/auth/forgot-password', [
            'email' => 'user@example.com',
        ])->assertOk()->assertJsonStructure(['message']);
    }

    public function test_forgot_password_rejette_email_inconnu(): void
    {
        $this->postJson('/api/auth/forgot-password', [
            'email' => 'inconnu@example.com',
        ])->assertStatus(422);
    }

    public function test_reset_password_echoue_avec_token_invalide(): void
    {
        User::factory()->create(['email' => 'reset@example.com']);

        $this->postJson('/api/auth/reset-password', [
            'token'                 => 'token-invalide',
            'email'                 => 'reset@example.com',
            'password'              => 'NouveauMdp12!',
            'password_confirmation' => 'NouveauMdp12!',
        ])->assertStatus(422);
    }
}
