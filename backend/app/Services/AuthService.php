<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Exception;

class AuthService
{
    // ===================== [Nguyễn Duy Khang - 4.2.5] ĐĂNG KÝ =====================

    /**
     * Tạo user mới và trả về cả token Sanctum để Controller không cần tạo token.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => 'user',
            'is_active' => true,
        ]);
        $token = $user->createToken('auth_token')->plainTextToken;
        return ['user' => $user, 'token' => $token];
    }

    // ===================== [Nguyễn Duy Khang - 4.2.6 & 4.2.7] ĐĂNG NHẬP & CHỐNG BRUTE FORCE =====================

    /**
     * Xác thực đăng nhập. Trả về ['user', 'token'] khi thành công.
     * Throws ValidationException nếu sai mật khẩu hoặc bị khóa.
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages(['email' => ['Email hoặc mật khẩu không chính xác.']]);
        }

        // Kiểm tra tài khoản đang bị khóa tạm thời
        if ($user->locked_until && Carbon::now()->lessThan($user->locked_until)) {
            throw ValidationException::withMessages([
                'password' => ['Bạn đã nhập sai quá số lần cho phép. Vui lòng thử lại sau.']
            ]);
        }

        if (!Hash::check($password, $user->password)) {
            $user->increment('login_attempts');
            Log::warning("Failed login attempt for email: {$email} from IP: " . request()->ip() . " at " . Carbon::now());

            if ($user->login_attempts >= 5) {
                $user->update(['locked_until' => Carbon::now()->addMinutes(5)]);
                Log::warning("Account locked (Brute Force) for email: {$email} from IP: " . request()->ip() . " until " . $user->locked_until);
                throw ValidationException::withMessages([
                    'password' => ['Bạn đã nhập sai quá số lần cho phép. Vui lòng thử lại sau.']
                ]);
            }

            throw ValidationException::withMessages([
                'password' => ['Email hoặc mật khẩu không chính xác.']
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Tài khoản của bạn đã bị khóa bởi quản trị viên.'],
            ]);
        }

        // Đăng nhập thành công → reset bộ đếm
        $user->update(['login_attempts' => 0, 'locked_until' => null]);
        $token = $user->createToken('auth_token')->plainTextToken;
        return ['user' => $user, 'token' => $token];
    }

    /**
     * Trả về dữ liệu bổ sung khi đăng nhập thất bại (locked_until, attempts_left).
     * Giúp Controller không cần tự query User model trong catch block.
     */
    public function getLoginErrorData(string $email): array
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return [];
        }
        if ($user->locked_until && Carbon::now()->lessThan($user->locked_until)) {
            return [
                'locked'      => true,
                'retry_after' => Carbon::now()->diffInSeconds($user->locked_until),
            ];
        }
        return ['attempts_left' => max(0, 5 - $user->login_attempts)];
    }

    /**
     * Vô hiệu hóa token Sanctum hiện tại của user.
     */
    public function logout($user): void
    {
        if ($user) {
            /** @var \Laravel\Sanctum\HasApiTokens $user */
            $user->currentAccessToken()->delete();
        }
    }

    // ===================== [Nguyễn Duy Khang - 4.2.3] DANH SÁCH USER =====================

    public function getAllUsers(array $filters = [])
    {
        return User::searchUsers($filters)->paginate(10);
    }

    // ===================== [Nguyễn Duy Khang - 4.2.1] THÊM USER (ADMIN) =====================

    public function createUser(array $data): User
    {
        return User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'phone'     => $data['phone'] ?? null,
            'address'   => $data['address'] ?? null,
            'role'      => $data['role'] ?? 'user',
            'is_active' => true,
        ]);
    }

    // ===================== [Nguyễn Duy Khang - 4.2.2] SỬA USER (ADMIN) =====================

    /**
     * Cập nhật thông tin user. Tích hợp Optimistic Locking kiểm tra $clientUpdatedAt.
     * Throws Exception 409 nếu dữ liệu đã bị thay đổi bởi tab khác.
     */
    public function updateUser(User $user, array $data, ?string $clientUpdatedAt = null): User
    {
        if ($clientUpdatedAt) {
            $clientTime = Carbon::parse($clientUpdatedAt);
            if ($user->updated_at && $user->updated_at->gt($clientTime)) {
                throw new Exception('Dữ liệu đã bị thay đổi ở tab khác, vui lòng tải lại!', 409);
            }
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return $user;
    }

    // ===================== [Nguyễn Duy Khang - 4.2.4] KHÓA / MỞ KHÓA / XÓA USER =====================

    public function lockUser(User $user): User
    {
        if ($user->role === 'admin') {
            throw new Exception('Không thể khóa tài khoản quản trị viên.', 422);
        }
        $user->update(['is_active' => false]);
        // Xóa toàn bộ Sanctum Token → user bị kick ra ngay lập tức
        $user->tokens()->delete();
        return $user;
    }

    public function unlockUser(User $user): User
    {
        $user->update(['is_active' => true, 'login_attempts' => 0, 'locked_until' => null]);
        return $user;
    }

    /**
     * Xóa user. Tích hợp: tìm user theo ID, kiểm tra self-delete, kiểm tra admin role, ghi log.
     * Throws Exception 404/422 nếu không hợp lệ.
     */
    public function deleteUser($userId, int $adminId): void
    {
        $user = User::find($userId);
        if (!$user) {
            throw new Exception('Tài khoản này không còn tồn tại.', 404);
        }
        if ($user->id === $adminId) {
            throw new Exception('Không thể tự xóa tài khoản đang đăng nhập.', 422);
        }
        if ($user->role === 'admin') {
            throw new Exception(__('messages.no_permission'), 422);
        }
        $user->delete();
        Log::info("Admin ID {$adminId} deleted User ID {$userId} at " . now());
    }

    // ===================== [Nguyễn Duy Khang - 4.2.11] QUÊN / ĐẶT LẠI MẬT KHẨU =====================

    /**
     * Tạo OTP, lưu hash vào DB, gửi email HTML cho user.
     * Throws Exception 404 nếu email không tồn tại.
     */
    public function forgotPassword(string $email): void
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new Exception('Email không tồn tại trong hệ thống.', 404);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($otp), 'created_at' => Carbon::now()]
        );

        Mail::send([], [], function ($message) use ($email, $otp, $user) {
            $message->to($email, $user->name)
                ->subject('Mã OTP đặt lại mật khẩu - Sellphones')
                ->html("
                    <div style='font-family: Inter, Arial, sans-serif; max-width: 480px; margin: 0 auto; background: #f9f9f9; padding: 40px 24px; border-radius: 16px;'>
                        <div style='text-align: center; margin-bottom: 32px;'>
                            <h1 style='font-size: 28px; font-weight: 700; margin: 0;'>
                                <span style='color: #2563eb;'>Sell</span><span style='color: #1e293b;'>phones</span>
                            </h1>
                        </div>
                        <div style='background: white; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); text-align: center;'>
                            <div style='font-size: 48px; margin-bottom: 16px;'>🔐</div>
                            <h2 style='font-size: 20px; font-weight: 700; color: #1e293b; margin: 0 0 8px;'>Mã OTP đặt lại mật khẩu</h2>
                            <p style='color: #64748b; font-size: 14px; margin: 0 0 28px;'>Xin chào <strong>{$user->name}</strong>, đây là mã OTP của bạn:</p>
                            <div style='background: linear-gradient(135deg, #eff6ff, #eef2ff); border: 2px dashed #bfdbfe; border-radius: 12px; padding: 20px; margin-bottom: 24px;'>
                                <span style='font-size: 42px; font-weight: 800; letter-spacing: 16px; color: #2563eb; font-family: monospace;'>{$otp}</span>
                            </div>
                            <p style='color: #ef4444; font-size: 12px; font-weight: 600; margin: 0 0 8px;'>⏱ Mã có hiệu lực trong <strong>15 phút</strong></p>
                            <p style='color: #94a3b8; font-size: 12px; margin: 0;'>Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.</p>
                        </div>
                        <p style='text-align: center; color: #cbd5e1; font-size: 11px; margin-top: 24px;'>© " . date('Y') . " Sellphones. Tất cả quyền được bảo lưu.</p>
                    </div>
                ");
        });
    }

    /**
     * Xác minh OTP và đặt lại mật khẩu mới.
     * Throws Exception 404/422 nếu không hợp lệ hoặc OTP hết hạn.
     */
    public function resetPassword(string $email, string $otp, string $newPassword): void
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new Exception('Email không tồn tại trong hệ thống.', 404);
        }

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();

        if (!$record || !Hash::check($otp, $record->token)) {
            throw new Exception('Mã OTP không chính xác.', 422);
        }

        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            throw new Exception('Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.', 422);
        }

        $user->update(['password' => Hash::make($newPassword)]);
        DB::table('password_reset_tokens')->where('email', $email)->delete();
    }
}