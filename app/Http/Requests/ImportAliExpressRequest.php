<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportAliExpressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSeller() ?? false;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:1000'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'markup_coefficient' => ['nullable', 'numeric', 'min:1', 'max:10'],
            'markup_fixed' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ];
    }
}
