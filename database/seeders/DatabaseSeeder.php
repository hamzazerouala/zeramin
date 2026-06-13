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
        ]);

        // Jeu de données de démonstration (sauf en production).
        if (! app()->environment('production')) {
            $this->call(DemoSeeder::class);
        }
    }
}
