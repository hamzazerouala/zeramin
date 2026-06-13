<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(private Google2FA $google2fa)
    {
    }

    /**
     * Génère un secret TOTP et l'URL otpauth:// (à transformer en QR côté client).
     */
    public function generateSecret(User $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $otpauthUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret,
        );

        return ['secret' => $secret, 'otpauth_url' => $otpauthUrl];
    }

    /**
     * Vérifie un code TOTP (fenêtre de tolérance ±1 intervalle).
     */
    public function verify(string $secret, string $code): bool
    {
        return $this->google2fa->verifyKey($secret, $code, 1);
    }

    /**
     * Codes de secours (récupération) à usage unique.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        return collect(range(1, $count))
            ->map(fn () => strtoupper(bin2hex(random_bytes(5))))
            ->all();
    }
}
