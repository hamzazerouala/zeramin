<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Cart;
use Illuminate\Http\Request;

trait ResolvesCart
{
    /**
     * Récupère (ou crée) le panier courant : utilisateur connecté via
     * customer_id, sinon identifiant de session / en-tête X-Cart-Token.
     */
    protected function resolveCart(Request $request, bool $create = true): ?Cart
    {
        if ($user = $request->user()) {
            $query = Cart::where('customer_id', $user->id);

            return $create
                ? $query->firstOrCreate(['customer_id' => $user->id])
                : $query->first();
        }

        $token = $this->cartToken($request);
        $query = Cart::whereNull('customer_id')->where('session_id', $token);

        return $create
            ? $query->firstOrCreate(['session_id' => $token, 'customer_id' => null])
            : $query->first();
    }

    protected function cartToken(Request $request): string
    {
        return $request->header('X-Cart-Token')
            ?? ($request->hasSession() ? $request->session()->getId() : 'guest');
    }
}
