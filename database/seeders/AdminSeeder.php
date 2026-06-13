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
            ['email' => 'admin@dropshop.local'],
            [
                'name' => 'Administrateur',
                'password' => Hash::make('ChangeMe!2026'),
                'user_type' => 'admin',
                'email_verified_at' => now(),
            ]
        );
    }
}
