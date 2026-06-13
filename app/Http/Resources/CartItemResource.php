<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $unit = (float) ($this->product->display_price ?? 0);

        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'title' => $this->whenLoaded('product', fn () => $this->product?->title),
            'thumbnail' => $this->whenLoaded('product', fn () => $this->product?->images?->first()?->image_url),
            'unit_price' => $unit,
            'quantity' => $this->quantity,
            'subtotal' => round($unit * $this->quantity, 2),
        ];
    }
}
