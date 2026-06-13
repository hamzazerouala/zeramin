<?php

namespace App\Services;

use App\Exceptions\AliExpressScrapeException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Scraping AliExpress (Phase 1).
 *
 * Stratégie en cascade :
 *   1. window.runParams (JSON embarqué, source la plus riche : titre, prix,
 *      images, variantes/SKU, note) ;
 *   2. JSON-LD (script application/ld+json) ;
 *   3. balises Open Graph.
 *
 * AliExpress étant rendu côté JS et anti-bot, prévoir en production un fetch
 * via navigateur headless (cf. config aliexpress.fetcher) ou l'API officielle.
 */
class AliExpressScraperService
{
    public function __construct(private ?Client $http = null)
    {
        $this->http ??= new Client([
            'timeout' => (int) config('aliexpress.timeout', 30),
            'headers' => [
                'User-Agent' => config('aliexpress.user_agent'),
                'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ],
        ]);
    }

    /**
     * @return array{aliexpress_product_id:?string,title:string,description:?string,images:array,price:float,currency:string,variants:array,rating:?float,reviews_count:?int}
     *
     * @throws AliExpressScrapeException
     */
    public function scrapeProduct(string $url): array
    {
        if (! config('aliexpress.scraper_enabled', true)) {
            throw new AliExpressScrapeException('Le scraping AliExpress est désactivé (ALIEXPRESS_SCRAPER_ENABLED).');
        }

        $html = $this->fetch($url);

        // 1) Source principale : window.runParams.
        $data = $this->fromRunParams($html);

        // 2) + 3) Compléments / fallback.
        if (! $data || empty($data['title'])) {
            $crawler = new Crawler($html);
            $data = $this->fromMetadata($crawler);
        }

        if (empty($data['title'])) {
            throw new AliExpressScrapeException("Impossible d'extraire les données du produit (page protégée, anti-bot ou format inconnu). Utiliser un fetch headless ou l'API officielle.");
        }

        $data['aliexpress_product_id'] ??= $this->extractProductId($url);

        return $data;
    }

    private function fetch(string $url): string
    {
        $fetcher = config('aliexpress.fetcher', 'http');

        if ($fetcher === 'headless') {
            // La configuration demande un fetch headless mais aucun service n'est
            // branché dans cette installation. On log un avertissement explicite
            // et on fait un fallback vers Guzzle en espérant que la page soit
            // accessible (acceptable en dev, prévisiblement bloqué en prod).
            \Illuminate\Support\Facades\Log::warning(
                'AliExpress fetcher=headless demandé mais non implémenté. '
                .'Fallback vers Guzzle HTTP (risque d\'échec anti-bot en production). '
                .'Branchez un service navigateur headless ou passez sur ALIEXPRESS_FETCHER=http.',
                ['url' => $url]
            );
        }

        try {
            return (string) $this->http->get($url)->getBody();
        } catch (GuzzleException $e) {
            throw new AliExpressScrapeException('Échec de récupération de la page AliExpress : '.$e->getMessage());
        }
    }

    public function extractProductId(string $url): ?string
    {
        if (preg_match('/item\/(\d+)\.html/', $url, $m)) {
            return $m[1];
        }
        if (preg_match('/\/(\d{6,})(?:\.html)?/', $url, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * Extraction depuis window.runParams = {...}.
     */
    private function fromRunParams(string $html): ?array
    {
        if (! preg_match('/window\.runParams\s*=\s*(\{.*?\});\s*<\/script>/s', $html, $m)
            && ! preg_match('/window\.runParams\s*=\s*(\{.*?\})\s*;/s', $html, $m)) {
            return null;
        }

        $json = json_decode($m[1], true);
        if (! is_array($json)) {
            return null;
        }

        $d = $json['data'] ?? $json;

        $title = $d['titleModule']['subject']
            ?? $d['productInfoComponent']['subject']
            ?? null;
        if (! $title) {
            return null;
        }

        // Prix
        $priceModule = $d['priceModule'] ?? [];
        $price = $priceModule['minActivityAmount']['value']
            ?? $priceModule['minAmount']['value']
            ?? $priceModule['maxAmount']['value']
            ?? 0;
        $currency = $priceModule['minAmount']['currency']
            ?? $priceModule['maxAmount']['currency']
            ?? 'USD';

        // Images
        $images = $d['imageModule']['imagePathList']
            ?? $d['imageModule']['summImagePathList']
            ?? [];

        // Variantes (SKU)
        $variants = [];
        foreach ($d['skuModule']['productSKUPropertyList'] ?? [] as $prop) {
            $values = array_map(
                fn ($v) => $v['propertyValueDisplayName'] ?? $v['propertyValueName'] ?? '',
                $prop['skuPropertyValues'] ?? []
            );
            $variants[] = [
                'name' => $prop['skuPropertyName'] ?? 'Option',
                'values' => array_values(array_filter($values)),
                'sku_id' => (string) ($prop['skuPropertyId'] ?? ''),
            ];
        }

        // Note
        $rating = $d['titleModule']['feedbackRating']['averageStar'] ?? null;
        $reviews = $d['titleModule']['feedbackRating']['totalValidNum']
            ?? $d['titleModule']['tradeCount']
            ?? null;

        return [
            'aliexpress_product_id' => isset($d['actionModule']['productId']) ? (string) $d['actionModule']['productId'] : null,
            'title' => trim($title),
            'description' => $d['descriptionModule']['descriptionUrl'] ?? null,
            'images' => $this->normalizeImages($images),
            'price' => (float) $price,
            'currency' => strtoupper((string) $currency),
            'variants' => $variants,
            'rating' => $rating !== null ? (float) $rating : null,
            'reviews_count' => $reviews !== null ? (int) $reviews : null,
        ];
    }

    /**
     * Fallback : JSON-LD puis Open Graph.
     */
    private function fromMetadata(Crawler $crawler): array
    {
        $jsonLd = [];
        $crawler->filter('script[type="application/ld+json"]')->each(function (Crawler $node) use (&$jsonLd) {
            $decoded = json_decode($node->text(), true);
            if (is_array($decoded) && (($decoded['@type'] ?? null) === 'Product')) {
                $jsonLd = $decoded;
            }
        });

        $og = function (string $property) use ($crawler): ?string {
            $node = $crawler->filter('meta[property="'.$property.'"]');

            return $node->count() ? $node->attr('content') : null;
        };

        $price = $jsonLd['offers']['price'] ?? $jsonLd['offers']['lowPrice'] ?? $og('product:price:amount');
        $currency = $jsonLd['offers']['priceCurrency'] ?? $og('product:price:currency') ?? 'USD';

        $images = [];
        if (! empty($jsonLd['image'])) {
            $images = is_array($jsonLd['image']) ? $jsonLd['image'] : [$jsonLd['image']];
        } elseif ($img = $og('og:image')) {
            $images = [$img];
        }

        return [
            'aliexpress_product_id' => null,
            'title' => $jsonLd['name'] ?? $og('og:title') ?? '',
            'description' => $jsonLd['description'] ?? $og('og:description'),
            'images' => $this->normalizeImages($images),
            'price' => (float) $price,
            'currency' => strtoupper((string) $currency),
            'variants' => [],
            'rating' => isset($jsonLd['aggregateRating']['ratingValue']) ? (float) $jsonLd['aggregateRating']['ratingValue'] : null,
            'reviews_count' => isset($jsonLd['aggregateRating']['reviewCount']) ? (int) $jsonLd['aggregateRating']['reviewCount'] : null,
        ];
    }

    private function normalizeImages(array $images): array
    {
        return array_values(array_filter(array_map(function ($url) {
            $url = (string) $url;
            // AliExpress sert des URLs protocol-relative (//ae01...).
            return str_starts_with($url, '//') ? 'https:'.$url : $url;
        }, $images)));
    }
}
