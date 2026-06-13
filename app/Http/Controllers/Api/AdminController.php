<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /** Liste des utilisateurs (filtrable par type). */
    public function users(Request $request): JsonResponse
    {
        $query = User::query()->with('sellerProfile')->latest();

        if ($request->filled('type')) {
            $query->where('user_type', $request->string('type'));
        }
        if ($request->filled('q')) {
            $term = $request->string('q');
            $query->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%"));
        }

        return response()->json($query->paginate((int) $request->integer('per_page', 25)));
    }

    /** Valide le KYC d'un vendeur. */
    public function verifyUser(Request $request, User $user): JsonResponse
    {
        if (! $user->sellerProfile) {
            return response()->json(['message' => "Cet utilisateur n'est pas vendeur."], 422);
        }

        $user->sellerProfile->update(['kyc_verified_at' => now()]);

        return response()->json(['message' => 'Vendeur vérifié.', 'verified_at' => $user->sellerProfile->kyc_verified_at]);
    }

    /** Litiges (commandes en statut disputed). */
    public function disputes(Request $request): JsonResponse
    {
        $orders = Order::where('status', 'disputed')
            ->with(['items', 'customer:id,name,email'])
            ->latest()
            ->paginate((int) $request->integer('per_page', 25));

        return OrderResource::collection($orders)->response();
    }

    /** Résolution d'un litige. */
    public function resolveDispute(Request $request, Order $order): JsonResponse
    {
        $data = $request->validate([
            'resolution' => ['required', 'in:refunded,delivered,cancelled'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $order->update([
            'status' => $data['resolution'],
            'notes_internal' => trim(($order->notes_internal ?? '')."\n[ADMIN] ".($data['note'] ?? $data['resolution'])),
        ]);

        return response()->json(['message' => 'Litige résolu.', 'status' => $order->status]);
    }
}
