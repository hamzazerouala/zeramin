<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@dropshop.test'],
            [
                'name'      => 'Administrateur',
                'email'     => 'admin@dropshop.test',
                'password'  => Hash::make('Admin123!'),
                'user_type' => 'admin',
            ]
        );

        $this->command->info('✓ Compte admin créé : admin@dropshop.test / Admin123!');
    }
}
