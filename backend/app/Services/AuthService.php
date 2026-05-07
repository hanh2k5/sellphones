<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AuthService
{
    /**
     * [Nguyễn Duy Khang - 4.2.5] Đăng ký tài khoản
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

    /**
     * [Nguyễn Duy Khang - 4.2.6] Đăng nhập
     * [Nguyễn Duy Khang - 4.2.7] Chống brute-force
     */
    public function login($email, $password)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages(['email' => ['Địa chỉ email này không tồn tại.']]);
        }

        if ($user->locked_until && Carbon::now()->lessThan($user->locked_until)) {
            $minutes = ceil(Carbon::now()->diffInMinutes($user->locked_until));
            throw ValidationException::withMessages([
                'password' => ["Tài khoản bị khóa tạm thời. Thử lại sau $minutes phút."]
            ]);
        }

        if (!Hash::check($password, $user->password)) {
            $user->increment('login_attempts');
            $remaining = 5 - $user->login_attempts;
            
            if ($user->login_attempts >= 5) {
                $user->update(['locked_until' => Carbon::now()->addMinutes(5)]);
                throw ValidationException::withMessages([
                    'password' => ['Nhập sai quá nhiều lần. Tài khoản bị khóa 5 phút.']
                ]);
            }

            throw ValidationException::withMessages([
                'password' => ["Mật khẩu không đúng. Còn $remaining lần thử."]
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages(['email' => ['Tài khoản đã bị khóa.']]);
        }

        $user->update(['login_attempts' => 0, 'locked_until' => null]);
        $token = $user->createToken('auth_token')->plainTextToken;

        return ['user' => $user, 'token' => $token];
    }
}
