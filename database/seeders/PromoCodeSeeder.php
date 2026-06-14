<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            [
                'code'             => 'BIENVENUE10',
                'type'             => 'percentage',
                'value'            => 10,
                'min_order_amount' => 0,
                'max_uses'         => 1000,
                'uses_count'       => 0,
                'is_active'        => true,
                'expires_at'       => now()->addYear(),
            ],
            [
                'code'             => 'SUMMER20',
                'type'             => 'percentage',
                'value'            => 20,
                'min_order_amount' => 50,
                'max_uses'         => 500,
                'uses_count'       => 0,
                'is_active'        => true,
                'expires_at'       => now()->addMonths(3),
            ],
        ];

        foreach ($codes as $code) {
            PromoCode::updateOrCreate(['code' => $code['code']], $code);
        }

        $this->command->info('✓ '.count($codes).' codes promo insérés.');
    }
}
