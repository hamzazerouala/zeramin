<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private TwoFactorService $twoFactor)
    {
    }

    /**
     * Inscription vendeur ou client. Crée le profil boutique si vendeur.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password, // hashé via cast
                'user_type' => $request->user_type,
                'phone' => $request->phone,
            ]);

            if ($user->isSeller()) {
                SellerProfile::create([
                    'user_id' => $user->id,
                    'shop_name' => $request->shop_name,
                    'shop_slug' => $this->uniqueShopSlug($request->shop_name),
                    'contact_email' => $user->email,
                    'country' => $request->country,
                    'is_active' => true,
                ]);
            }

            return $user;
        });

        $token = $user->createToken($request->input('device_name', 'api'))->plainTextToken;

        return response()->json([
            'user' => $user->load('sellerProfile'),
            'token' => $token,
        ], 201);
    }

    /**
     * Connexion. Gère le second facteur si activé.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants invalides.'],
            ]);
        }

        // Second facteur requis ?
        if ($user->two_factor_enabled) {
            if (! $request->filled('code')) {
                return response()->json([
                    'two_factor_required' => true,
                    'message' => 'Code d\'authentification à deux facteurs requis.',
                ], 423);
            }

            if (! $this->twoFactor->verify((string) $user->two_factor_secret, $request->code)) {
                throw ValidationException::withMessages([
                    'code' => ['Code 2FA invalide.'],
                ]);
            }
        }

        $token = $user->createToken($request->input('device_name', 'api'))->plainTextToken;

        return response()->json([
            'user' => $user->load('sellerProfile'),
            'token' => $token,
        ]);
    }

    /**
     * Déconnexion : révoque le token courant.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        // Réponse neutre (évite l'énumération d'emails).
        return response()->json([
            'message' => 'Si un compte existe, un email de réinitialisation a été envoyé.',
            'status' => __($status),
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Révoque tous les tokens existants après reset.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => 'Mot de passe réinitialisé.']);
    }

    /**
     * Étape 1 : génère un secret TOTP (non encore activé).
     */
    public function setupTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $this->twoFactor->generateSecret($user);

        $user->two_factor_secret = $data['secret'];
        $user->two_factor_enabled = false;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return response()->json([
            'secret' => $data['secret'],
            'otpauth_url' => $data['otpauth_url'],
            'message' => 'Scannez le QR code puis confirmez avec un code pour activer le 2FA.',
        ]);
    }

    /**
     * Étape 2 : confirme et active le 2FA, renvoie les codes de secours.
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if (! $user->two_factor_secret) {
            throw ValidationException::withMessages(['code' => ['Aucun secret 2FA initialisé.']]);
        }

        if (! $this->twoFactor->verify((string) $user->two_factor_secret, $request->code)) {
            throw ValidationException::withMessages(['code' => ['Code invalide.']]);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->two_factor_enabled = true;
        $user->two_factor_confirmed_at = now();
        $user->two_factor_recovery_codes = $recoveryCodes;
        $user->save();

        return response()->json([
            'message' => '2FA activé.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disableTwoFactor(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);
        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages(['password' => ['Mot de passe incorrect.']]);
        }

        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => '2FA désactivé.']);
    }

    private function uniqueShopSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'shop';
        $slug = $base;
        $i = 1;
        while (SellerProfile::where('shop_slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
