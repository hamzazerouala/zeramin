<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'title' => $this->title,
            'content' => $this->content,
            'verified_purchase' => (bool) $this->verified_purchase,
            'helpful_count' => $this->helpful_count,
            'author' => $this->whenLoaded('customer', fn () => $this->customer?->name),
            'created_at' => $this->created_at,
        ];
    }
}
