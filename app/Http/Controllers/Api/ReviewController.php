<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    /** Liste publique des avis d'un produit. */
    public function index(Product $product): JsonResponse
    {
        $reviews = $product->reviews()
            ->with('customer:id,name')
            ->latest()
            ->paginate(10);

        return ReviewResource::collection($reviews)->response();
    }

    /** Dépôt d'un avis (un seul par client et par produit). */
    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string', 'max:5000'],
        ]);

        $user = $request->user();

        if ($product->reviews()->where('customer_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['rating' => ['Vous avez déjà laissé un avis sur ce produit.']]);
        }

        // Achat vérifié : une commande de ce client contient ce produit.
        $verifiedOrder = Order::where('customer_id', $user->id)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest()
            ->first();

        $review = $product->reviews()->create([
            'customer_id' => $user->id,
            'order_id' => $verifiedOrder?->id,
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'verified_purchase' => (bool) $verifiedOrder,
        ]);

        $this->recalculateRating($product);

        return (new ReviewResource($review->load('customer:id,name')))->response()->setStatusCode(201);
    }

    private function recalculateRating(Product $product): void
    {
        $agg = $product->reviews()
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')
            ->first();

        $product->update([
            'rating' => round((float) $agg->avg_rating, 2),
            'rating_count' => (int) $agg->total,
        ]);
    }
}
