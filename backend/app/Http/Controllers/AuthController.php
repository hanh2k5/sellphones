<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * SV THỰC HIỆN: NGUYỄN DUY KHANG
 * MỤC: 4.2.1 -> 4.2.8 (XÁC THỰC, BẢO MẬT & QUẢN LÝ USER)
 */
class AuthController extends Controller
{
    /** @var AuthService */
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * [Nguyễn Duy Khang - 4.2.5] Đăng ký tài khoản + Validate
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|max:255|confirmed|not_regex:/\s/',
        ], [
            'password.not_regex' => 'Mật khẩu không được chứa khoảng trắng.',
        ]);

        $user = $this->authService->register($request->all());
        return response()->json([
            'data' => [
                'user' => (new UserResource($user))->resolve(),
                'token' => $user->createToken('auth_token')->plainTextToken,
            ],
            'message' => 'Đăng ký thành công!'
        ], 201);
    }

    /**
     * [Nguyễn Duy Khang - 4.2.6] Đăng nhập hệ thống
     * [Nguyễn Duy Khang - 4.2.7] Giới hạn đăng nhập sai (Brute Force)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email|max:255',
            'password' => 'required|string|max:255',
        ]);

        try {
            $result = $this->authService->login($request->email, $request->password);
            
            return response()->json([
                'user'  => (new UserResource($result['user']))->resolve(),
                'token' => $result['token'],
                'message' => 'Đăng nhập thành công!'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 401);
        }
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            /** @var \Laravel\Sanctum\HasApiTokens $user */
            $user->currentAccessToken()->delete();
        }
        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    public function me()
    {
        return (new UserResource(Auth::user()))->resolve();
    }

    /**
     * [Nguyễn Duy Khang - 4.2.3] Danh sách người dùng (Admin)
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin();
        $users = $this->authService->getAllUsers($request->all());
        return UserResource::collection($users);
    }

    /**
     * [Nguyễn Duy Khang - 4.2.1] Thêm mới người dùng (Admin)
     */
    public function storeUser(Request $request)
    {
        $this->authorizeAdmin();
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:8',
            'phone'    => 'nullable|string|max:15',
            'address'  => 'nullable|string|max:500',
            'role'     => 'required|in:admin,user',
        ]);

        $user = $this->authService->createUser($request->all());
        return response()->json(['message' => 'Tạo người dùng thành công!', 'user' => $user], 201);
    }

    /**
     * [Nguyễn Duy Khang - 4.2.2] Cập nhật người dùng (Admin)
     */
    public function updateUser(Request $request, User $user)
    {
        $this->authorizeAdmin();
        $request->validate([
            'name'     => 'nullable|string|max:255',
            'email'    => 'nullable|email|unique:users,email,' . $user->id,
            'phone'    => 'nullable|string|max:15',
            'address'  => 'nullable|string|max:500',
            'role'     => 'nullable|in:admin,user',
            'password' => 'nullable|string|min:8',
        ]);

        $updatedUser = $this->authService->updateUser($user, $request->all());
        return response()->json(['message' => 'Cập nhật thành công!', 'user' => $updatedUser]);
    }

    /**
     * [Nguyễn Duy Khang - 4.2.4] Khóa tài khoản người dùng (Admin)
     */
    public function lock(User $user)
    {
        $this->authorizeAdmin();
        try {
            $updatedUser = $this->authService->lockUser($user);
            return response()->json(['message' => 'Đã khóa tài khoản.', 'user' => $updatedUser]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * [Nguyễn Duy Khang - 4.2.4] Mở khóa tài khoản người dùng (Admin)
     */
    public function unlock(User $user)
    {
        $this->authorizeAdmin();
        $updatedUser = $this->authService->unlockUser($user);
        return response()->json(['message' => 'Đã mở khóa tài khoản.', 'user' => $updatedUser]);
    }

    /**
     * [Nguyễn Duy Khang - 4.2.4] Xóa người dùng (Admin)
     */
    public function destroyUser(User $user)
    {
        $this->authorizeAdmin();
        try {
            $this->authService->deleteUser($user);
            return response()->json(['message' => 'Đã xóa người dùng.']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * [Nguyễn Duy Khang - 4.2.11] Quên mật khẩu - Gửi OTP qua email
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại trong hệ thống.'], 404);
        }

        // Tạo OTP 6 số ngẫu nhiên
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Lưu OTP vào database password_reset_tokens (hashed)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => Hash::make($otp),
                'created_at' => Carbon::now()
            ]
        );

        // Gửi email chứa OTP
        Mail::send([], [], function ($message) use ($request, $otp, $user) {
            $message->to($request->email, $user->name)
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

        return response()->json([
            'message' => 'Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.',
        ]);
    }

    /**
     * [Nguyễn Duy Khang - 4.2.11] Đặt lại mật khẩu bằng OTP
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed|not_regex:/\s/',
        ], [
            'otp.required'            => 'Vui lòng nhập mã OTP.',
            'otp.size'                => 'Mã OTP phải có đúng 6 chữ số.',
            'password.min'            => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'password.not_regex'      => 'Mật khẩu không được chứa khoảng trắng.',
            'password.confirmed'      => 'Mật khẩu xác nhận không khớp.',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại trong hệ thống.'], 404);
        }

        // Lấy record OTP từ DB
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json(['message' => 'Mã OTP không chính xác.'], 422);
        }

        // Kiểm tra thời hạn OTP (15 phút)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.'], 422);
        }

        // Cập nhật mật khẩu mới (đã băm)
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Xóa OTP sau khi dùng
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.']);
    }

    private function authorizeAdmin()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Yêu cầu quyền quản trị.');
        }
    }
}
