<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSellerSeeder extends Seeder
{
    public function run(): void
    {
        // Créer le vendeur de démonstration
        $seller = User::updateOrCreate(
            ['email' => 'vendeur@dropshop.test'],
            [
                'name'      => 'TechStore Demo',
                'email'     => 'vendeur@dropshop.test',
                'password'  => Hash::make('Vendeur123!'),
                'user_type' => 'seller',
            ]
        );

        // Créer le profil boutique
        $profile = SellerProfile::updateOrCreate(
            ['user_id' => $seller->id],
            [
                'user_id'       => $seller->id,
                'shop_name'     => 'TechStore Demo',
                'shop_slug'     => 'techstore-demo',
                'bio'           => 'Boutique de démonstration proposant des produits high-tech à prix compétitifs. Livraison rapide, satisfaction garantie.',
                'contact_email' => 'vendeur@dropshop.test',
                'country'       => 'FR',
                'is_active'     => true,
                'commission_rate' => 0.05,
            ]
        );

        $categoryId = Category::where('slug', 'electronique')->value('id');

        // 5 produits fictifs
        $products = [
            [
                'title'       => 'Écouteurs Bluetooth Pro X1',
                'slug'        => 'ecouteurs-bluetooth-pro-x1',
                'description' => 'Écouteurs sans fil avec réduction de bruit active, autonomie 30h, son stéréo HD.',
                'cost_price'  => 18.00,
                'markup_coefficient' => 2.0,
                'markup_fixed'       => 5.0,
                'stock_platform' => 150,
                'rating'      => 4.3,
                'rating_count'=> 42,
            ],
            [
                'title'       => 'Montre Connectée FitBand 3',
                'slug'        => 'montre-connectee-fitband-3',
                'description' => 'Suivi santé complet : fréquence cardiaque, sommeil, GPS. Compatible iOS & Android.',
                'cost_price'  => 28.00,
                'markup_coefficient' => 2.0,
                'markup_fixed'       => 5.0,
                'stock_platform' => 80,
                'rating'      => 4.1,
                'rating_count'=> 28,
            ],
            [
                'title'       => 'Chargeur Rapide USB-C 65W',
                'slug'        => 'chargeur-rapide-usbc-65w',
                'description' => 'Chargeur universel 65W compatible avec laptops, tablettes et smartphones.',
                'cost_price'  => 7.50,
                'markup_coefficient' => 2.5,
                'markup_fixed'       => 5.0,
                'stock_platform' => 300,
                'rating'      => 4.6,
                'rating_count'=> 95,
            ],
            [
                'title'       => 'Support Téléphone Voiture Magnétique',
                'slug'        => 'support-telephone-voiture-magnetique',
                'description' => 'Support magnétique universel pour tableau de bord, rotation 360°.',
                'cost_price'  => 3.50,
                'markup_coefficient' => 3.0,
                'markup_fixed'       => 4.0,
                'stock_platform' => 500,
                'rating'      => 4.4,
                'rating_count'=> 180,
            ],
            [
                'title'       => 'Lampe de Bureau LED Smart',
                'slug'        => 'lampe-bureau-led-smart',
                'description' => 'Lampe LED dimmable avec chargeur sans fil intégré, contrôle tactile, température de couleur réglable.',
                'cost_price'  => 14.00,
                'markup_coefficient' => 2.0,
                'markup_fixed'       => 8.0,
                'stock_platform' => 120,
                'rating'      => 4.2,
                'rating_count'=> 56,
            ],
        ];

        $pricing = app(\App\Services\PricingService::class);

        foreach ($products as $productData) {
            $finalPrice = $pricing->finalPrice(
                (float) $productData['cost_price'],
                (float) $productData['markup_coefficient'],
                (float) $productData['markup_fixed'],
            );
            Product::updateOrCreate(
                ['slug' => $productData['slug']],
                array_merge($productData, [
                    'seller_id'   => $profile->id,
                    'category_id' => $categoryId,
                    'cost_currency' => 'EUR',
                    'final_price_calculated' => $finalPrice,
                    'is_active'   => true,
                    'featured'    => true,
                    'synced_at'   => now(),
                ])
            );
        }

        $this->command->info('✓ Vendeur démo + '.count($products).' produits créés.');
        $this->command->info('  Email : vendeur@dropshop.test / Vendeur123!');
    }
}
