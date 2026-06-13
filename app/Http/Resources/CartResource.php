<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->whenLoaded('items');
        $subtotal = $this->relationLoaded('items') ? $this->subtotal() : null;

        return [
            'id' => $this->id,
            'coupon_code' => $this->coupon_code,
            'currency' => config('aliexpress.store_currency', 'EUR'),
            'items' => CartItemResource::collection($items),
            'subtotal' => $subtotal,
            'item_count' => $this->relationLoaded('items') ? $this->items->sum('quantity') : null,
        ];
    }
}
