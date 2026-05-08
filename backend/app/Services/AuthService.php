<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Exception;

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

    /**
     * [Nguyễn Duy Khang - 4.2.6] Đăng nhập hệ thống (Kiểm tra Hash)
     * [Nguyễn Duy Khang - 4.2.7] Chống tấn công dò mật khẩu (Brute Force)
     */
    public function login($email, $password)
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages(['email' => ['Địa chỉ email này không tồn tại trong hệ thống.']]);
        }

        // Kiểm tra xem tài khoản có đang bị khóa tạm thời không
        if ($user->locked_until && Carbon::now()->lessThan($user->locked_until)) {
            $minutes = ceil(Carbon::now()->diffInMinutes($user->locked_until));
            throw ValidationException::withMessages([
                'password' => ["🔒 Tài khoản đang bị khóa tạm thời. Vui lòng thử lại sau $minutes phút."]
            ]);
        }

        if (!Hash::check($password, $user->password)) {
            // Tăng số lần nhập sai
            $user->increment('login_attempts');
            $remaining = 5 - $user->login_attempts;
            
            if ($user->login_attempts >= 5) {
                $user->update(['locked_until' => Carbon::now()->addMinutes(5)]);
                throw ValidationException::withMessages([
                    'password' => ['Bạn đã nhập sai quá 5 lần. Tài khoản bị khóa tạm thời trong 5 phút.']
                ]);
            }

            throw ValidationException::withMessages([
                'password' => ["Mật khẩu không chính xác. Bạn còn $remaining lần thử trước khi bị khóa."]
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Tài khoản của bạn đã bị khóa bởi quản trị viên.'],
            ]);
        }

        // Đăng nhập thành công -> Reset bộ đếm
        $user->update([
            'login_attempts' => 0,
            'locked_until'   => null
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    public function lockUser(User $user)
    {
        if ($user->role === 'admin') {
            throw new Exception('Không thể khóa tài khoản quản trị viên.', 422);
        }
        $user->update(['is_active' => false]);
        return $user;
    }

    public function unlockUser(User $user)
    {
        $user->update(['is_active' => true, 'login_attempts' => 0, 'locked_until' => null]);
        return $user;
    }
}
