<?php

namespace App\Jobs;

use App\Exceptions\AliExpressScrapeException;
use App\Models\Product;
use App\Services\AliExpressScraperService;
use App\Services\PricingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncAliExpressStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(public Product $product)
    {
    }

    public function handle(AliExpressScraperService $scraper, PricingService $pricing): void
    {
        if (! $this->product->aliexpress_url) {
            return;
        }

        try {
            $data = $scraper->scrapeProduct($this->product->aliexpress_url);
        } catch (AliExpressScrapeException $e) {
            Log::warning('Sync AliExpress échouée', ['product_id' => $this->product->id, 'error' => $e->getMessage()]);

            return;
        }

        $cost = $pricing->convertToStoreCurrency($data['price'], $data['currency']);

        $this->product->update([
            'cost_price' => $cost,
            'final_price_calculated' => $pricing->finalPrice(
                $cost,
                (float) $this->product->markup_coefficient,
                (float) $this->product->markup_fixed,
            ),
            'rating' => $data['rating'] ?? $this->product->rating,
            'rating_count' => $data['reviews_count'] ?? $this->product->rating_count,
            'synced_at' => now(),
        ]);
    }
}
