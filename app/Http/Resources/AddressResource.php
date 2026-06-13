<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'address' => $this->address,
            'address_complement' => $this->address_complement,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'province' => $this->province,
            'country' => $this->country,
            'type' => $this->type,
            'is_default' => (bool) $this->is_default,
        ];
    }
}
