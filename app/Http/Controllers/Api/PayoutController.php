<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function __construct(private PayoutService $payoutService) {}

    /** Liste les virements du vendeur connecté. */
    public function index(Request $request): JsonResponse
    {
        $seller = $request->user()->sellerProfile;

        if (! $seller) {
            return response()->json(['message' => 'Profil vendeur introuvable.'], 422);
        }

        $payouts = Payout::where('seller_id', $seller->id)
            ->latest()
            ->paginate(20);

        // Montant disponible pour un nouveau virement.
        $pending = $this->payoutService->computePendingAmount($seller);

        return response()->json([
            ...$payouts->toArray(),
            'pending_amount' => $pending,
        ]);
    }

    /** Le vendeur demande un virement. */
    public function request(Request $request): JsonResponse
    {
        $seller = $request->user()->sellerProfile;

        if (! $seller) {
            return response()->json(['message' => 'Profil vendeur introuvable.'], 422);
        }

        try {
            $payout = $this->payoutService->requestPayout($seller);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            ...$payout->toArray(),
            'message' => 'Demande de virement enregistrée.',
        ], 201);
    }
}
