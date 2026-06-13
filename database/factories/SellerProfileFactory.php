<?php

namespace Database\Factories;

use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SellerProfile> */
class SellerProfileFactory extends Factory
{
    protected $model = SellerProfile::class;

    public function definition(): array
    {
        $shop = fake()->unique()->company();

        return [
            'user_id' => User::factory()->seller(),
            'shop_name' => $shop,
            'shop_slug' => Str::slug($shop).'-'.fake()->unique()->numberBetween(1, 99999),
            'bio' => fake()->paragraph(),
            'contact_email' => fake()->companyEmail(),
            'country' => fake()->randomElement(['FR', 'BE', 'DE', 'ES']),
            'commission_rate' => fake()->randomFloat(2, 5, 15),
            'avg_rating' => fake()->randomFloat(2, 3.5, 5),
            'is_active' => true,
        ];
    }
}
