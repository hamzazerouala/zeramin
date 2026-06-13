<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Mail\OrderShippedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\ShippingZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SellerController extends Controller
{
    /** KPIs et vue d'ensemble. */
    public function dashboard(Request $request): JsonResponse
    {
        $seller = $request->user()->sellerProfile;
        $startMonth = now()->startOfMonth();

        $paid = Order::where('seller_id', $seller->id)->where('payment_status', 'succeeded');

        $revenueMonth = (clone $paid)->where('created_at', '>=', $startMonth)->sum('total_amount');
        $ordersTotal = (clone $paid)->count();
        $avgBasket = $ordersTotal ? (clone $paid)->avg('total_amount') : 0;

        $statusCounts = Order::where('seller_id', $seller->id)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $lowStock = Product::where('seller_id', $seller->id)
            ->where('is_active', true)
            ->where('stock_platform', '<', 5)
            ->select(['id', 'title', 'stock_platform'])
            ->limit(20)->get();

        $recentOrders = Order::where('seller_id', $seller->id)
            ->latest()->limit(10)->with('items')->get();

        return response()->json([
            'revenue_month' => round((float) $revenueMonth, 2),
            'orders_total' => $ordersTotal,
            'avg_basket' => round((float) $avgBasket, 2),
            'orders_by_status' => $statusCounts,
            'low_stock_products' => $lowStock,
            'recent_orders' => OrderResource::collection($recentOrders),
        ]);
    }

    /** Statistiques détaillées sur une période. */
    public function analytics(Request $request): JsonResponse
    {
        $seller = $request->user()->sellerProfile;
        $days = (int) $request->integer('days', 30);
        $from = now()->subDays($days)->startOfDay();

        $base = Order::where('seller_id', $seller->id)
            ->where('payment_status', 'succeeded')
            ->where('created_at', '>=', $from);

        $salesByDay = (clone $base)
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('SUM(total_amount) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('day')->orderBy('day')->get();

        $topProducts = OrderItem::query()
            ->select('product_id', DB::raw('SUM(quantity) as units'), DB::raw('SUM(subtotal) as revenue'))
            ->whereIn('order_id', (clone $base)->select('id'))
            ->groupBy('product_id')
            ->orderByDesc('units')
            ->limit(10)
            ->with('product:id,title')
            ->get();

        return response()->json([
            'period_days' => $days,
            'total_revenue' => round((float) (clone $base)->sum('total_amount'), 2),
            'total_orders' => (clone $base)->count(),
            'avg_basket' => round((float) ((clone $base)->avg('total_amount') ?? 0), 2),
            'unique_customers' => (clone $base)->distinct('customer_id')->count('customer_id'),
            'sales_by_day' => $salesByDay,
            'top_products' => $topProducts,
        ]);
    }

    /** Commandes du vendeur (filtrables). */
    public function orders(Request $request): JsonResponse
    {
        $seller = $request->user()->sellerProfile;
        $query = Order::where('seller_id', $seller->id)->with('items');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->date('to'));
        }

        return OrderResource::collection(
            $query->latest()->paginate((int) $request->integer('per_page', 20))
        )->response();
    }

    public function orderShow(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        return (new OrderResource($order->load('items.product')))->response();
    }

    /** Mise à jour du statut + suivi (le vendeur renseigne le tracking). */
    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        $data = $request->validate([
            'status' => ['required', 'in:processing,shipped,in_transit,delivered,disputed,cancelled'],
            'aliexpress_order_number' => ['nullable', 'string', 'max:255'],
            'aliexpress_tracking_id' => ['nullable', 'string', 'max:255'],
            'tracking_url' => ['nullable', 'url'],
            'estimated_delivery_date' => ['nullable', 'date'],
        ]);

        $wasShipped = $order->status === 'shipped';
        $order->fill($data);
        if ($data['status'] === 'delivered' && ! $order->delivered_at) {
            $order->delivered_at = now();
        }
        $order->save();

        // Notifie le client au passage en "shipped".
        if (! $wasShipped && $data['status'] === 'shipped') {
            $to = $order->customer?->email ?? $order->email_guest;
            if ($to) {
                Mail::to($to)->send(new OrderShippedMail($order->fresh()));
            }
        }

        return (new OrderResource($order->fresh()->load('items')))->response();
    }

    /** Lecture des paramètres boutique (zones de livraison, intégrations). */
    public function settings(Request $request): JsonResponse
    {
        $seller = $request->user()->sellerProfile;

        return response()->json([
            'shop' => $seller,
            'shipping_zones' => $seller->shippingZones,
            'integrations' => [
                'stripe_connect_id' => $seller->stripe_connect_id,
                'aliexpress_account' => $seller->aliexpress_account,
                'aliexpress_configured' => ! empty($seller->aliexpress_password_encrypted),
                'last_sync' => $seller->products()->max('synced_at'),
            ],
        ]);
    }

    /** Mise à jour des paramètres : zones de livraison + intégrations. */
    public function updateSettings(Request $request): JsonResponse
    {
        $seller = $request->user()->sellerProfile;

        $data = $request->validate([
            'stripe_connect_id' => ['nullable', 'string', 'max:255'],
            'aliexpress_account' => ['nullable', 'string', 'max:255'],
            'aliexpress_password' => ['nullable', 'string', 'max:255'],
            'shipping_zones' => ['nullable', 'array'],
            'shipping_zones.*.country' => ['required_with:shipping_zones', 'string', 'max:10'],
            'shipping_zones.*.type' => ['required_with:shipping_zones', 'in:fixed,percentage,free'],
            'shipping_zones.*.cost' => ['nullable', 'numeric', 'min:0'],
            'shipping_zones.*.is_free_above' => ['nullable', 'boolean'],
            'shipping_zones.*.free_above_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_zones.*.delivery_days' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($seller, $data, $request) {
            $shopUpdate = array_filter([
                'stripe_connect_id' => $data['stripe_connect_id'] ?? null,
                'aliexpress_account' => $data['aliexpress_account'] ?? null,
            ], fn ($v) => $v !== null);

            if (! empty($data['aliexpress_password'])) {
                // Chiffré automatiquement via le cast "encrypted".
                $shopUpdate['aliexpress_password_encrypted'] = $data['aliexpress_password'];
            }
            if ($shopUpdate) {
                $seller->update($shopUpdate);
            }

            if ($request->has('shipping_zones')) {
                ShippingZone::where('seller_id', $seller->id)->delete();
                foreach ($data['shipping_zones'] ?? [] as $zone) {
                    $seller->shippingZones()->create($zone);
                }
            }
        });

        return response()->json([
            'message' => 'Paramètres mis à jour.',
            'shipping_zones' => $seller->shippingZones()->get(),
        ]);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless($order->seller_id === $request->user()->sellerProfile?->id, 403, 'Commande hors de votre boutique.');
    }
}
