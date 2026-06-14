<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Payout;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\PayoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutServiceTest extends TestCase
{
    use RefreshDatabase;

    private PayoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PayoutService;
    }

    public function test_compute_pending_amount_sans_commandes(): void
    {
        $seller = SellerProfile::factory()->create();

        $result = $this->service->computePendingAmount($seller);

        $this->assertEquals(0.0, $result['total_amount']);
        $this->assertEquals(0.0, $result['net_amount']);
    }

    public function test_compute_pending_amount_avec_commandes_livrees(): void
    {
        $seller = SellerProfile::factory()->create();
        Order::factory()->create([
            'seller_id'      => $seller->id,
            'status'         => 'delivered',
            'payment_status' => 'succeeded',
            'total_amount'   => 100.00,
        ]);

        $result = $this->service->computePendingAmount($seller);

        $this->assertEquals(100.0, $result['total_amount']);
        $this->assertEquals(5.0, $result['fees_amount']); // 5% par défaut
        $this->assertEquals(95.0, $result['net_amount']);
    }

    public function test_request_payout_echoue_si_montant_nul(): void
    {
        $seller = SellerProfile::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->service->requestPayout($seller);
    }

    public function test_request_payout_cree_le_virement(): void
    {
        $seller = SellerProfile::factory()->create();
        Order::factory()->create([
            'seller_id'      => $seller->id,
            'status'         => 'delivered',
            'payment_status' => 'succeeded',
            'total_amount'   => 200.00,
        ]);

        $payout = $this->service->requestPayout($seller);

        $this->assertInstanceOf(Payout::class, $payout);
        $this->assertEquals('pending', $payout->status);
        $this->assertEquals(200.0, (float) $payout->total_amount);
        $this->assertEquals(10.0, (float) $payout->fees_amount); // 5%
        $this->assertEquals(190.0, (float) $payout->net_amount);
    }
}
