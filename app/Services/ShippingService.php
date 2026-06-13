<?php

namespace App\Services;

use App\Models\SellerProfile;
use App\Models\ShippingZone;
use Illuminate\Support\Collection;

class ShippingService
{
    /**
     * Calcule les frais de port pour un vendeur et une destination.
     *
     * @param  Collection  $cartItems  CartItem chargés avec product
     */
    public function calculate(SellerProfile $seller, string $country, Collection $cartItems): float
    {
        $subtotal = $this->subtotal($cartItems);

        $zone = ShippingZone::where('seller_id', $seller->id)
            ->where('country', $country)
            ->first()
            ?? ShippingZone::where('seller_id', $seller->id)
                ->where('country', 'WORLD')
                ->first();

        // Aucune zone définie : livraison gratuite par défaut.
        if (! $zone) {
            return 0.0;
        }

        if ($zone->is_free_above && $zone->free_above_amount !== null
            && $subtotal >= (float) $zone->free_above_amount) {
            return 0.0;
        }

        return match ($zone->type) {
            'free' => 0.0,
            'percentage' => round($subtotal * ((float) $zone->cost / 100), 2),
            default => round((float) $zone->cost, 2), // fixed
        };
    }

    public function subtotal(Collection $cartItems): float
    {
        return (float) $cartItems->sum(
            fn ($item) => (float) $item->product->display_price * (int) $item->quantity
        );
    }
}
