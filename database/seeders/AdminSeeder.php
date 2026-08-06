<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@skanida.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('skanida1968'),
                'email_verified_at' => now(),
            ]
        );
    }
}