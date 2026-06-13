<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;

class PricingService
{
    /**
     * Prix de vente = (coût × coefficient) + marge fixe, arrondi au centime.
     */
    public function finalPrice(float $cost, float $coefficient, float $fixed): float
    {
        return round(($cost * $coefficient) + $fixed, 2);
    }

    /**
     * Convertit un montant vers la devise de la boutique (taux indicatifs).
     */
    public function convertToStoreCurrency(float $amount, string $fromCurrency): float
    {
        $rates = config('aliexpress.fx_to_store', ['EUR' => 1.0]);
        $rate = $rates[strtoupper($fromCurrency)] ?? 1.0;

        return round($amount * $rate, 2);
    }

    /**
     * Recalcule et persiste le prix dénormalisé d'un produit.
     */
    public function recalculateProduct(Product $product): float
    {
        $price = $this->finalPrice(
            (float) $product->cost_price,
            (float) $product->markup_coefficient,
            (float) $product->markup_fixed,
        );

        $product->final_price_calculated = $price;
        $product->saveQuietly();

        return $price;
    }

    /**
     * Prix d'une variante : utilise ses propres marges si définies,
     * sinon retombe sur celles du produit parent.
     */
    public function variantPrice(ProductVariant $variant): float
    {
        $cost = $variant->cost_price_variant ?? $variant->product->cost_price;
        $coef = $variant->markup_coefficient ?? $variant->product->markup_coefficient;
        $fixed = $variant->markup_fixed ?? $variant->product->markup_fixed;

        return $this->finalPrice((float) $cost, (float) $coef, (float) $fixed);
    }
}
