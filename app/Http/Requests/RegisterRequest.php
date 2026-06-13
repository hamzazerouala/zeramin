<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'user_type' => ['required', Rule::in(['buyer', 'seller'])],
            'phone' => ['nullable', 'string', 'max:30'],

            // Requis uniquement pour un vendeur.
            'shop_name' => ['required_if:user_type,seller', 'nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'size:2'],
        ];
    }
}
