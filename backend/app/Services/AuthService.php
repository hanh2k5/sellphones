<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * [Nguyễn Duy Khang - 4.2.5] Đăng ký tài khoản (Tạo User mới)
     */
    public function register(array $data)
    {
        return User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'address'  => $data['address'] ?? null,
            'phone'    => $data['phone'] ?? null,
            'role'     => 'user',
            'is_active'=> true,
        ]);
    }
}
