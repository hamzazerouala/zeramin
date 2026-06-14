<?php

namespace Tests\Unit\Services;

use App\Services\PricingService;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    private PricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PricingService;
    }

    public function test_final_price_calcule_correctement(): void
    {
        // (10 * 2.5) + 5 = 30
        $this->assertEquals(30.0, $this->service->finalPrice(10.0, 2.5, 5.0));
    }

    public function test_final_price_arrondi_au_centime(): void
    {
        // (7.77 * 1.5) + 2.5 = 14.155 → 14.16
        $this->assertEquals(14.16, $this->service->finalPrice(7.77, 1.5, 2.5));
    }

    public function test_final_price_marge_fixe_zero(): void
    {
        // (20 * 3.0) + 0 = 60
        $this->assertEquals(60.0, $this->service->finalPrice(20.0, 3.0, 0.0));
    }

    public function test_convert_to_store_currency_eur(): void
    {
        // Devise identique, taux = 1.0 par défaut
        $result = $this->service->convertToStoreCurrency(100.0, 'EUR');
        $this->assertEquals(100.0, $result);
    }

    public function test_convert_to_store_currency_devue_inconnue_fallback(): void
    {
        // Devise inconnue → taux = 1.0 (fallback)
        $result = $this->service->convertToStoreCurrency(50.0, 'XYZ');
        $this->assertEquals(50.0, $result);
    }
}
