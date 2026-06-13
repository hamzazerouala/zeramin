<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\SellerProfile;
use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        if ($categories->isEmpty()) {
            $this->call(CategorySeeder::class);
            $categories = Category::all();
        }

        // 3 vendeurs de démo avec mot de passe connu.
        for ($s = 1; $s <= 3; $s++) {
            $user = User::factory()->seller()->create([
                'name' => "Vendeur Démo $s",
                'email' => "vendeur$s@dropshop.local",
                'password' => Hash::make('Password123!'),
            ]);

            $shopName = "Boutique Démo $s";
            $seller = SellerProfile::create([
                'user_id' => $user->id,
                'shop_name' => $shopName,
                'shop_slug' => Str::slug($shopName),
                'bio' => "Boutique de démonstration n°$s — produits variés en dropshipping.",
                'contact_email' => "vendeur$s@dropshop.local",
                'country' => 'FR',
                'commission_rate' => 10,
                'avg_rating' => 4.5,
                'is_active' => true,
            ]);

            // Zones de livraison.
            ShippingZone::create([
                'seller_id' => $seller->id, 'country' => 'FR', 'type' => 'fixed',
                'cost' => 4.90, 'is_free_above' => true, 'free_above_amount' => 50, 'delivery_days' => 10,
            ]);
            ShippingZone::create([
                'seller_id' => $seller->id, 'country' => 'WORLD', 'type' => 'fixed',
                'cost' => 9.90, 'is_free_above' => false, 'delivery_days' => 25,
            ]);

            // 8 produits par vendeur, avec images et variantes.
            Product::factory()->count(8)->create([
                'seller_id' => $seller->id,
                'category_id' => fn () => $categories->random()->id,
            ])->each(function (Product $product) {
                $seed = $product->id;
                foreach (range(0, 2) as $i) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => "https://picsum.photos/seed/{$seed}{$i}/600/600",
                        'alt_text' => $product->title,
                        'sort_order' => $i,
                    ]);
                }

                // Variantes couleur pour la moitié des produits.
                if ($product->id % 2 === 0) {
                    foreach (['Noir', 'Blanc', 'Bleu'] as $color) {
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'aliexpress_sku_id' => (string) random_int(100000, 999999),
                            'variant_name' => "Couleur: $color",
                            'variant_values' => ['color' => $color],
                            'cost_price_variant' => $product->cost_price,
                            'stock_aliexpress_variant' => random_int(0, 50),
                        ]);
                    }
                }
            });
        }

        // 1 client de démo.
        User::factory()->create([
            'name' => 'Client Démo',
            'email' => 'client@dropshop.local',
            'password' => Hash::make('Password123!'),
            'user_type' => 'buyer',
        ]);
    }
}
