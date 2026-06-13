<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /** Historique des commandes du client connecté. */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->with('items')
            ->latest()
            ->paginate((int) $request->integer('per_page', 15));

        return OrderResource::collection($orders)->response();
    }

    /** Détail d'une commande (client propriétaire uniquement). */
    public function show(Request $request, Order $order): JsonResponse
    {
        abort_unless($order->customer_id === $request->user()->id, 403);

        return (new OrderResource($order->load('items.product')))->response();
    }
}
