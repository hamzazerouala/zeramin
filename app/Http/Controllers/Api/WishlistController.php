<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /** Produits favoris du client. */
    public function index(Request $request): JsonResponse
    {
        $productIds = Wishlist::where('customer_id', $request->user()->id)->pluck('product_id');

        $products = Product::whereIn('id', $productIds)
            ->with(['seller', 'images'])
            ->get();

        return ProductResource::collection($products)->response();
    }

    /** Ajoute un produit aux favoris (idempotent). */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate(['product_id' => ['required', 'exists:products,id']]);

        Wishlist::firstOrCreate([
            'customer_id' => $request->user()->id,
            'product_id' => $data['product_id'],
        ]);

        return response()->json(['message' => 'Ajouté aux favoris.', 'in_wishlist' => true], 201);
    }

    /** Retire un produit des favoris. */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        Wishlist::where('customer_id', $request->user()->id)
            ->where('product_id', $product->id)
            ->delete();

        return response()->json(['message' => 'Retiré des favoris.', 'in_wishlist' => false]);
    }
}
