<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSeller() ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:999999'],
            'cost_currency' => ['nullable', 'string', 'size:3'],
            'markup_coefficient' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'markup_fixed' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'stock_platform' => ['nullable', 'integer', 'min:0'],
            'shipping_days_estimated' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array'],
            'images.*' => ['url'],
        ];
    }
}
