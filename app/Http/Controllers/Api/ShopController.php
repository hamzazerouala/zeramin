<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SellerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /** Page publique d'une boutique. */
    public function show(string $slug): JsonResponse
    {
        $shop = SellerProfile::where('shop_slug', $slug)
            ->where('is_active', true)
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();

        return response()->json([
            'id' => $shop->id,
            'shop_name' => $shop->shop_name,
            'shop_slug' => $shop->shop_slug,
            'logo_url' => $shop->logo_url,
            'banner_url' => $shop->banner_url,
            'bio' => $shop->bio,
            'contact_email' => $shop->contact_email,
            'country' => $shop->country,
            'avg_rating' => (float) $shop->avg_rating,
            'products_count' => $shop->products_count,
        ]);
    }

    /** Boutique du vendeur connecté (vue privée). */
    public function mine(Request $request): JsonResponse
    {
        $shop = $request->user()->sellerProfile;

        return response()->json($shop);
    }

    public function update(Request $request): JsonResponse
    {
        $shop = $request->user()->sellerProfile;

        $data = $request->validate([
            'shop_name' => ['sometimes', 'string', 'max:255'],
            'logo_url' => ['nullable', 'url'],
            'banner_url' => ['nullable', 'url'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'contact_email' => ['nullable', 'email'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        $shop->update($data);

        return response()->json([
            'message' => 'Boutique mise à jour.',
            'shop' => $shop->fresh(),
        ]);
    }
}
