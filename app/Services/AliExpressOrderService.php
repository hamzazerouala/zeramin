<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * Placement de commande chez AliExpress.
 *
 * Sur cPanel mutualisé, Selenium n'est pas disponible : le mode par défaut est
 * "manual" (le vendeur place la commande puis renseigne le tracking). Le mode
 * "api" est réservé à la Phase 2 (AliExpress Developer API).
 */
class AliExpressOrderService
{
    /**
     * Tente de placer la commande. Retourne le numéro de suivi si obtenu,
     * null si fulfillment manuel requis.
     */
    public function placeOrder(Order $order): ?string
    {
        $mode = config('aliexpress.fulfillment_mode', 'manual');

        return match ($mode) {
            'api' => $this->placeViaApi($order),
            default => $this->markForManualFulfillment($order),
        };
    }

    private function markForManualFulfillment(Order $order): ?string
    {
        Log::info('Commande en attente de fulfillment manuel AliExpress', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        // Statut reste "processing" : le vendeur place la commande et ajoutera
        // ensuite le tracking via PUT /api/seller/orders/{id}/status.
        return null;
    }

    private function placeViaApi(Order $order): ?string
    {
        // Phase 2 : intégration AliExpress Developer API.
        throw new \RuntimeException('Le mode API AliExpress sera implémenté en Phase 2.');
    }
}
