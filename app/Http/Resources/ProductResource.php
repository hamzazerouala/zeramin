<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'price' => (float) $this->display_price,
            'cost_price' => $this->when($request->user()?->isSeller(), (float) $this->cost_price),
            'currency' => config('aliexpress.store_currency', 'EUR'),
            'rating' => (float) $this->rating,
            'rating_count' => $this->rating_count,
            'in_stock' => $this->stock_platform > 0,
            'stock' => $this->stock_platform,
            'featured' => (bool) $this->featured,
            'shipping_days' => $this->shipping_days_estimated,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller?->id,
                'shop_name' => $this->seller?->shop_name,
                'shop_slug' => $this->seller?->shop_slug,
            ]),
            'thumbnail' => $this->whenLoaded('images', fn () => $this->images->first()?->image_url),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'created_at' => $this->created_at,
        ];
    }
}
