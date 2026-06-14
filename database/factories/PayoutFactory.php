<?php

namespace Database\Factories;

use App\Models\Payout;
use App\Models\SellerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Payout> */
class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    public function definition(): array
    {
        $total = fake()->randomFloat(2, 50, 1000);
        $fee   = round($total * 0.05, 2);

        return [
            'seller_id'    => SellerProfile::factory(),
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end'   => now()->subMonth()->endOfMonth(),
            'total_amount' => $total,
            'fees_amount'  => $fee,
            'net_amount'   => round($total - $fee, 2),
            'status'       => fake()->randomElement(['pending', 'paid']),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status'           => 'paid',
            'stripe_payout_id' => 'po_'.fake()->bothify('???###???###'),
        ]);
    }
}
