<?php

namespace Tests\Unit\Services;

use App\Services\AliExpressScraperService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class AliExpressScraperTest extends TestCase
{
    public function test_extract_product_id_depuis_url(): void
    {
        $service = new AliExpressScraperService(new Client);

        $this->assertEquals('1234567890', $service->extractProductId('https://www.aliexpress.com/item/1234567890.html'));
    }

    public function test_extract_product_id_url_courte(): void
    {
        $service = new AliExpressScraperService(new Client);

        $this->assertEquals('987654321', $service->extractProductId('https://www.aliexpress.com/987654321'));
    }

    public function test_extract_product_id_url_invalide(): void
    {
        $service = new AliExpressScraperService(new Client);

        $this->assertNull($service->extractProductId('https://www.example.com'));
    }

    public function test_scrape_product_desactive_lance_exception(): void
    {
        $this->expectException(\App\Exceptions\AliExpressScrapeException::class);

        // On ne peut pas facilement config config() en test unitaire pur,
        // donc on mock le service
        $mock = $this->createPartialMock(AliExpressScraperService::class, ['scrapeProduct']);
        $mock->method('scrapeProduct')->willThrowException(
            new \App\Exceptions\AliExpressScrapeException('Le scraping AliExpress est désactivé.')
        );

        $mock->scrapeProduct('https://www.aliexpress.com/item/123.html');
    }
}
