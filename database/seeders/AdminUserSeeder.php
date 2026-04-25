<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'mehedihasanahad07@gmail.com'],
            [
                'name' => 'Admin',
                'email' => 'mehedihasanahad07@gmail.com',
                'password' => Hash::make('eu7pQRbVUaHRcBn$'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
