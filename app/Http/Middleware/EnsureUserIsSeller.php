<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isSeller()) {
            return response()->json(['message' => 'Accès réservé aux vendeurs.'], 403);
        }

        if (! $user->sellerProfile || ! $user->sellerProfile->is_active) {
            return response()->json(['message' => 'Profil vendeur inactif.'], 403);
        }

        return $next($request);
    }
}
