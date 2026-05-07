<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('11111111'),
            'role' => 'admin',
            'is_active' => true,
            'address' => '1 Võ Văn Ngân, TP. Thủ Đức, TP.HCM',
            'phone' => '0123456789',
            'email_verified_at' => now(),
        ]);

        // User mẫu
        User::create([
            'name' => 'Hạnh',
            'email' => 'hanh2005k@gmail.com',
            'password' => Hash::make('11111111'),
            'role' => 'user',
            'is_active' => true,
            'address' => '250 Nguyễn Văn Cừ, Quận 5, TP.HCM',
            'phone' => '0987654321',
            'email_verified_at' => now(),
        ]);
    }
}
