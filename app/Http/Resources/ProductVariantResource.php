<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->variant_name,
            'values' => $this->variant_values,
            'stock' => $this->stock_aliexpress_variant,
        ];
    }
}
