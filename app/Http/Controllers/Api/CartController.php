<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesCart;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\PromoCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    use ResolvesCart;

    public function show(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);

        return (new CartResource($this->loaded($cart)))->response();
    }

    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'variant_id' => ['nullable', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $product = Product::active()->findOrFail($data['product_id']);
        $qty = $data['quantity'] ?? 1;

        if ($product->stock_platform < $qty) {
            throw ValidationException::withMessages(['quantity' => ['Stock insuffisant.']]);
        }

        $cart = $this->resolveCart($request);

        $item = $cart->items()
            ->where('product_id', $product->id)
            ->where('variant_id', $data['variant_id'] ?? null)
            ->first();

        if ($item) {
            $item->increment('quantity', $qty);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $data['variant_id'] ?? null,
                'quantity' => $qty,
            ]);
        }

        return (new CartResource($this->loaded($cart)))->response()->setStatusCode(201);
    }

    public function updateItem(Request $request, CartItem $item): JsonResponse
    {
        $this->authorizeItem($request, $item);

        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']]);

        if ($item->product->stock_platform < $data['quantity']) {
            throw ValidationException::withMessages(['quantity' => ['Stock insuffisant.']]);
        }

        $item->update(['quantity' => $data['quantity']]);

        return (new CartResource($this->loaded($item->cart)))->response();
    }

    public function removeItem(Request $request, CartItem $item): JsonResponse
    {
        $this->authorizeItem($request, $item);
        $cart = $item->cart;
        $item->delete();

        return (new CartResource($this->loaded($cart)))->response();
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $cart = $this->resolveCart($request);

        $promo = PromoCode::where('code', $request->code)->first();
        if (! $promo || ! $promo->isValid()) {
            throw ValidationException::withMessages(['code' => ['Code promo invalide ou expiré.']]);
        }

        $cart->update(['coupon_code' => $promo->code]);

        return (new CartResource($this->loaded($cart)))->response();
    }

    public function removeCoupon(Request $request): JsonResponse
    {
        $cart = $this->resolveCart($request);
        $cart->update(['coupon_code' => null]);

        return (new CartResource($this->loaded($cart)))->response();
    }

    private function loaded($cart)
    {
        return $cart->load(['items.product.images', 'items.variant']);
    }

    private function authorizeItem(Request $request, CartItem $item): void
    {
        $cart = $this->resolveCart($request, create: false);
        abort_unless($cart && $item->cart_id === $cart->id, 403, 'Article hors de votre panier.');
    }
}
