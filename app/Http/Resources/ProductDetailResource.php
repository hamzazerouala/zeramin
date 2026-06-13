<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->display_price,
            'currency' => config('aliexpress.store_currency', 'EUR'),
            'rating' => (float) $this->rating,
            'rating_count' => $this->rating_count,
            'in_stock' => $this->stock_platform > 0,
            'stock' => $this->stock_platform,
            'shipping_days' => $this->shipping_days_estimated,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ]),
            'seller' => $this->whenLoaded('seller', fn () => [
                'id' => $this->seller?->id,
                'shop_name' => $this->seller?->shop_name,
                'shop_slug' => $this->seller?->shop_slug,
                'avg_rating' => (float) $this->seller?->avg_rating,
            ]),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'reviews_count' => $this->whenCounted('reviews'),
        ];
    }
}
