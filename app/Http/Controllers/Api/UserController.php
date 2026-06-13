<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('sellerProfile'));
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'avatar_url' => ['nullable', 'url'],
        ]);

        $request->user()->update($data);

        return response()->json([
            'message' => 'Profil mis à jour.',
            'user' => $request->user()->fresh(),
        ]);
    }

    public function addresses(Request $request): JsonResponse
    {
        return AddressResource::collection(
            $request->user()->addresses()->latest('is_default')->get()
        )->response();
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $data = $this->validateAddress($request);

        $address = DB::transaction(function () use ($request, $data) {
            if (! empty($data['is_default'])) {
                $request->user()->addresses()->update(['is_default' => false]);
            }

            return $request->user()->addresses()->create($data);
        });

        return (new AddressResource($address))->response()->setStatusCode(201);
    }

    public function updateAddress(Request $request, Address $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $data = $this->validateAddress($request);

        DB::transaction(function () use ($request, $address, $data) {
            if (! empty($data['is_default'])) {
                $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            }
            $address->update($data);
        });

        return (new AddressResource($address->fresh()))->response();
    }

    public function destroyAddress(Request $request, Address $address): JsonResponse
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $address->delete();

        return response()->json(['message' => 'Adresse supprimée.']);
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:255'],
            'address_complement' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'country' => ['required', 'string', 'size:2'],
            'type' => ['nullable', 'in:shipping,billing,both'],
            'is_default' => ['nullable', 'boolean'],
        ]);
    }
}
