<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 3, 80);
        $coef = fake()->randomElement([1.5, 2.0, 2.5, 3.0]);
        $fixed = fake()->randomElement([0, 2.5, 5]);
        $title = Str::title(fake()->words(3, true));

        return [
            'seller_id' => SellerProfile::factory(),
            'category_id' => Category::factory(),
            'aliexpress_product_id' => (string) fake()->numberBetween(1000000000, 9999999999),
            'aliexpress_url' => 'https://www.aliexpress.com/item/'.fake()->numberBetween(1000000000, 9999999999).'.html',
            'title' => $title,
            'description' => fake()->paragraphs(3, true),
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'cost_price' => $cost,
            'cost_currency' => 'EUR',
            'markup_coefficient' => $coef,
            'markup_fixed' => $fixed,
            'final_price_calculated' => round($cost * $coef + $fixed, 2),
            'rating' => fake()->randomFloat(2, 3.5, 5),
            'rating_count' => fake()->numberBetween(0, 2000),
            'stock_aliexpress' => fake()->numberBetween(0, 500),
            'stock_platform' => fake()->numberBetween(0, 100),
            'shipping_days_estimated' => fake()->numberBetween(5, 30),
            'is_active' => true,
            'featured' => fake()->boolean(20),
            'synced_at' => now(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['featured' => true]);
    }
}
