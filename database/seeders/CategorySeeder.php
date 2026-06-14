<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Électronique',  'slug' => 'electronique',  'description' => 'Smartphones, ordinateurs, accessoires high-tech'],
            ['name' => 'Mode',          'slug' => 'mode',          'description' => 'Vêtements, chaussures, accessoires de mode'],
            ['name' => 'Maison',        'slug' => 'maison',        'description' => 'Décoration, mobilier, équipement de maison'],
            ['name' => 'Sport',         'slug' => 'sport',         'description' => 'Équipements sportifs, fitness, plein air'],
            ['name' => 'Beauté',        'slug' => 'beaute',        'description' => 'Cosmétiques, soins, parfums'],
            ['name' => 'Jouets',        'slug' => 'jouets',        'description' => 'Jouets, jeux, loisirs créatifs'],
            ['name' => 'Auto',          'slug' => 'auto',          'description' => 'Accessoires automobile, pièces détachées'],
            ['name' => 'Jardinage',     'slug' => 'jardinage',     'description' => 'Outillage, plantes, mobilier de jardin'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }

        $this->command->info('✓ '.count($categories).' catégories insérées.');
    }
}
