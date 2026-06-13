<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payout;
use App\Models\SellerProfile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    /**
     * Calcule le montant dû à un vendeur sur une période donnée
     * (commandes "delivered" non encore incluses dans un payout).
     */
    public function computePendingAmount(SellerProfile $seller): array
    {
        $fee = (float) config('services.platform.fee', 0.05);

        // Commandes livrées du vendeur non encore couvertes par un payout.
        $total = Order::where('status', 'delivered')
            ->where('seller_id', $seller->id)
            ->whereDoesntHave('payouts', fn ($q) => $q->where('seller_id', $seller->id))
            ->sum('total_amount');

        $fees = round((float) $total * $fee, 2);
        $net  = round((float) $total - $fees, 2);

        return [
            'total_amount' => round((float) $total, 2),
            'fees_amount'  => $fees,
            'net_amount'   => $net,
            'fee_rate'     => $fee,
        ];
    }

    /**
     * Crée une demande de virement (payout en statut "pending").
     */
    public function requestPayout(SellerProfile $seller): Payout
    {
        $amounts = $this->computePendingAmount($seller);

        if ($amounts['net_amount'] <= 0) {
            throw new \RuntimeException('Aucun montant disponible pour un virement.');
        }

        return DB::transaction(function () use ($seller, $amounts) {
            $payout = Payout::create([
                'seller_id'    => $seller->id,
                'period_start' => Carbon::now()->startOfMonth(),
                'period_end'   => Carbon::now()->endOfMonth(),
                'total_amount' => $amounts['total_amount'],
                'fees_amount'  => $amounts['fees_amount'],
                'net_amount'   => $amounts['net_amount'],
                'status'       => 'pending',
            ]);

            return $payout;
        });
    }
}
