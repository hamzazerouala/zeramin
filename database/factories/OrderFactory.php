<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $total = fake()->randomFloat(2, 20, 500);

        return [
            'order_number'   => Order::generateOrderNumber(),
            'seller_id'      => SellerProfile::factory(),
            'customer_id'    => User::factory(),
            'subtotal'       => $total,
            'shipping_cost'  => fake()->randomFloat(2, 0, 20),
            'tax_amount'     => 0,
            'total_amount'   => $total,
            'currency'       => 'EUR',
            'status'         => fake()->randomElement(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
            'payment_status' => fake()->randomElement(['pending', 'succeeded']),
            'payment_method' => 'stripe',
            'shipping_address' => [
                'line1' => fake()->streetAddress(),
                'city'  => fake()->city(),
                'zip'   => fake()->postcode(),
                'country' => 'FR',
            ],
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status'         => 'delivered',
            'payment_status' => 'succeeded',
            'delivered_at'   => now()->subDays(2),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status'         => 'pending',
            'payment_status' => 'pending',
        ]);
    }
}
