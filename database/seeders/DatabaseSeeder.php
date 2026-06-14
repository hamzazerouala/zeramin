<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            AdminSeeder::class,
            PromoCodeSeeder::class,
        ]);

        // Jeu de données de démonstration (sauf en production).
        if (! app()->environment('production')) {
            $this->call(DemoSellerSeeder::class);
        }
    }
}
