<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Mode & Vêtements',
            'Électronique',
            'Maison & Jardin',
            'Beauté & Santé',
            'Sport & Loisirs',
            'Jouets & Enfants',
            'Auto & Moto',
            'Accessoires',
        ];

        foreach ($categories as $i => $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'sort_order' => $i]
            );
        }
    }
}
