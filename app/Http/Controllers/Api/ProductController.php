<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AliExpressScrapeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportAliExpressRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Services\AliExpressScraperService;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(private PricingService $pricing)
    {
    }

    /** Catalogue public, filtré et trié. */
    public function index(Request $request): JsonResponse
    {
        $products = $this->filteredQuery($request)
            ->with(['seller', 'category', 'images'])
            ->paginate((int) $request->integer('per_page', 20));

        return ProductResource::collection($products)->response();
    }

    /** Recherche plein-texte (FULLTEXT MySQL, fallback LIKE). */
    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => ['required', 'string', 'min:2', 'max:255']]);
        $term = $request->string('q');

        $query = Product::query()->active()->with(['seller', 'category', 'images']);

        if (DB::getDriverName() === 'mysql') {
            $query->whereFullText(['title', 'description'], $term);
        } else {
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%"));
        }

        return ProductResource::collection(
            $query->paginate((int) $request->integer('per_page', 20))
        )->response();
    }

    /** Détail produit public (par id ou slug). */
    public function show(string $product): JsonResponse
    {
        $model = Product::active()
            ->where('id', $product)->orWhere('slug', $product)
            ->with(['seller', 'category', 'images', 'variants'])
            ->withCount('reviews')
            ->firstOrFail();

        return (new ProductDetailResource($model))->response();
    }

    /** Produits d'une boutique (slug). */
    public function byShop(string $slug, Request $request): JsonResponse
    {
        $products = Product::active()
            ->whereHas('seller', fn ($q) => $q->where('shop_slug', $slug))
            ->with(['seller', 'category', 'images'])
            ->paginate((int) $request->integer('per_page', 20));

        return ProductResource::collection($products)->response();
    }

    // ----- Espace vendeur -----

    public function sellerIndex(Request $request): JsonResponse
    {
        $seller = $request->user()->sellerProfile;

        $query = Product::where('seller_id', $seller->id)->with(['category', 'images']);

        if ($request->filled('status')) {
            $query->where('is_active', $request->boolean('status'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->boolean('low_stock')) {
            $query->where('stock_platform', '<', 5);
        }

        return ProductResource::collection(
            $query->latest()->paginate((int) $request->integer('per_page', 20))
        )->response();
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $seller = $request->user()->sellerProfile;
        $data = $request->validated();

        $product = DB::transaction(function () use ($seller, $data) {
            $product = new Product($data);
            $product->seller_id = $seller->id;
            $product->cost_currency = $data['cost_currency'] ?? config('aliexpress.store_currency', 'EUR');
            $product->markup_coefficient = $data['markup_coefficient'] ?? config('aliexpress.default_markup_coefficient');
            $product->markup_fixed = $data['markup_fixed'] ?? config('aliexpress.default_markup_fixed');
            $product->slug = $this->uniqueSlug($data['title']);
            $product->final_price_calculated = $this->pricing->finalPrice(
                (float) $product->cost_price,
                (float) $product->markup_coefficient,
                (float) $product->markup_fixed,
            );
            $product->save();

            $this->syncImages($product, $data['images'] ?? []);

            return $product;
        });

        return (new ProductDetailResource($product->load(['images', 'category', 'seller'])))
            ->response()->setStatusCode(201);
    }

    /** Import depuis une URL AliExpress (scraping Phase 1). */
    public function importFromAliExpress(ImportAliExpressRequest $request, AliExpressScraperService $scraper): JsonResponse
    {
        $seller = $request->user()->sellerProfile;

        try {
            $data = $scraper->scrapeProduct($request->url);
        } catch (AliExpressScrapeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $costStore = $this->pricing->convertToStoreCurrency($data['price'], $data['currency']);
        $coef = (float) ($request->markup_coefficient ?? config('aliexpress.default_markup_coefficient'));
        $fixed = (float) ($request->markup_fixed ?? config('aliexpress.default_markup_fixed'));

        $product = DB::transaction(function () use ($seller, $request, $data, $costStore, $coef, $fixed) {
            $product = Product::create([
                'seller_id' => $seller->id,
                'category_id' => $request->category_id,
                'aliexpress_product_id' => $data['aliexpress_product_id'],
                'aliexpress_url' => $request->url,
                'aliexpress_raw' => $data,
                'title' => $data['title'],
                'description' => $data['description'],
                'slug' => $this->uniqueSlug($data['title']),
                'cost_price' => $costStore,
                'cost_currency' => config('aliexpress.store_currency', 'EUR'),
                'markup_coefficient' => $coef,
                'markup_fixed' => $fixed,
                'final_price_calculated' => $this->pricing->finalPrice($costStore, $coef, $fixed),
                'rating' => $data['rating'] ?? 0,
                'rating_count' => $data['reviews_count'] ?? 0,
                'is_active' => true,
                'synced_at' => now(),
            ]);

            $this->syncImages($product, $data['images']);
            $this->syncVariants($product, $data['variants'] ?? []);

            return $product;
        });

        return (new ProductDetailResource($product->load(['images', 'category', 'seller'])))
            ->response()->setStatusCode(201);
    }

    public function sellerShow(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwnership($request, $product);

        return (new ProductDetailResource($product->load(['images', 'variants', 'category', 'seller'])))
            ->response();
    }

    public function update(StoreProductRequest $request, Product $product): JsonResponse
    {
        $this->authorizeOwnership($request, $product);
        $data = $request->validated();

        $product->fill($data);
        $product->final_price_calculated = $this->pricing->finalPrice(
            (float) $product->cost_price,
            (float) $product->markup_coefficient,
            (float) $product->markup_fixed,
        );
        $product->save();

        if (array_key_exists('images', $data)) {
            $product->images()->delete();
            $this->syncImages($product, $data['images'] ?? []);
        }

        return (new ProductDetailResource($product->load(['images', 'category', 'seller'])))->response();
    }

    /** Archivage (soft : is_active=false). */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwnership($request, $product);
        $product->update(['is_active' => false]);

        return response()->json(['message' => 'Produit archivé.']);
    }

    /** Statistiques d'un produit (unités vendues, CA). */
    public function stats(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwnership($request, $product);

        $agg = $product->orderItems()
            ->selectRaw('COALESCE(SUM(quantity),0) as units, COALESCE(SUM(subtotal),0) as revenue, COUNT(DISTINCT order_id) as orders')
            ->first();

        return response()->json([
            'product_id' => $product->id,
            'units_sold' => (int) $agg->units,
            'revenue' => (float) $agg->revenue,
            'orders' => (int) $agg->orders,
            'stock_platform' => $product->stock_platform,
            'rating' => (float) $product->rating,
        ]);
    }

    // ----- Helpers -----

    private function filteredQuery(Request $request)
    {
        $query = Product::query()->active();

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->string('category')));
        }
        if ($request->filled('min_price')) {
            $query->where('final_price_calculated', '>=', $request->float('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('final_price_calculated', '<=', $request->float('max_price'));
        }
        if ($request->filled('min_rating')) {
            $query->where('rating', '>=', $request->float('min_rating'));
        }
        if ($request->boolean('featured')) {
            $query->where('featured', true);
        }

        return match ((string) $request->string('sort')) {
            'price_asc' => $query->orderBy('final_price_calculated'),
            'price_desc' => $query->orderByDesc('final_price_calculated'),
            'rating' => $query->orderByDesc('rating'),
            'recent' => $query->latest(),
            default => $query->orderByDesc('featured')->latest(),
        };
    }

    private function authorizeOwnership(Request $request, Product $product): void
    {
        abort_unless($product->seller_id === $request->user()->sellerProfile?->id, 403, 'Ce produit ne vous appartient pas.');
    }

    private function syncVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variant) {
            ProductVariant::create([
                'product_id' => $product->id,
                'aliexpress_sku_id' => $variant['sku_id'] ?? null,
                'variant_name' => $variant['name'] ?? 'Option',
                'variant_values' => $variant['values'] ?? [],
                'cost_price_variant' => $product->cost_price,
                'stock_aliexpress_variant' => 0,
            ]);
        }
    }

    private function syncImages(Product $product, array $images): void
    {
        foreach (array_values($images) as $i => $url) {
            ProductImage::create([
                'product_id' => $product->id,
                'image_url' => $url,
                'sort_order' => $i,
            ]);
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug(Str::limit($title, 80, '')) ?: 'produit';
        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }

        return $slug;
    }
}
