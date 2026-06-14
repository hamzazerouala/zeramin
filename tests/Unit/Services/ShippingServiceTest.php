<?php

namespace Tests\Unit\Services;

use App\Models\SellerProfile;
use App\Models\ShippingZone;
use App\Services\ShippingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ShippingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShippingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ShippingService;
    }

    public function test_livraison_gratuite_si_aucune_zone(): void
    {
        $seller = SellerProfile::factory()->create();
        $items  = new Collection;

        $this->assertEquals(0.0, $this->service->calculate($seller, 'FR', $items));
    }

    public function test_zone_type_fixed(): void
    {
        $seller = SellerProfile::factory()->create();
        ShippingZone::create([
            'seller_id' => $seller->id,
            'country'   => 'FR',
            'type'      => 'fixed',
            'cost'      => 5.99,
        ]);

        $items = new Collection;
        $this->assertEquals(5.99, $this->service->calculate($seller, 'FR', $items));
    }

    public function test_zone_type_free(): void
    {
        $seller = SellerProfile::factory()->create();
        ShippingZone::create([
            'seller_id' => $seller->id,
            'country'   => 'FR',
            'type'      => 'free',
            'cost'      => 0,
        ]);

        $items = new Collection;
        $this->assertEquals(0.0, $this->service->calculate($seller, 'FR', $items));
    }

    public function test_fallback_vers_zone_WORLD(): void
    {
        $seller = SellerProfile::factory()->create();
        ShippingZone::create([
            'seller_id' => $seller->id,
            'country'   => 'WORLD',
            'type'      => 'fixed',
            'cost'      => 15.0,
        ]);

        $items = new Collection;
        // JP n'a pas de zone spécifique → fallback WORLD
        $this->assertEquals(15.0, $this->service->calculate($seller, 'JP', $items));
    }

    public function test_gratuit_au_dessus_seuil(): void
    {
        $seller = SellerProfile::factory()->create();
        ShippingZone::create([
            'seller_id'       => $seller->id,
            'country'         => 'FR',
            'type'            => 'fixed',
            'cost'            => 8.0,
            'is_free_above'   => true,
            'free_above_amount' => 50.0,
        ]);

        // Créer un faux cartItem avec un produit à 60€
        $product = \App\Models\Product::factory()->create([
            'seller_id' => $seller->id,
            'final_price_calculated' => 60.0,
        ]);
        $item = new \App\Models\CartItem([
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);
        $item->setRelation('product', $product);
        $items = collect([$item]);

        $this->assertEquals(0.0, $this->service->calculate($seller, 'FR', $items));
    }
}
