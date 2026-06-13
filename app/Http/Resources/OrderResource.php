<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'subtotal' => (float) $this->subtotal,
            'shipping_cost' => (float) $this->shipping_cost,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'shipping_address' => $this->shipping_address,
            'shipping_method' => $this->shipping_method,
            'tracking' => [
                'aliexpress_order_number' => $this->aliexpress_order_number,
                'tracking_id' => $this->aliexpress_tracking_id,
                'tracking_url' => $this->tracking_url,
                'estimated_delivery_date' => $this->estimated_delivery_date,
                'delivered_at' => $this->delivered_at,
            ],
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
