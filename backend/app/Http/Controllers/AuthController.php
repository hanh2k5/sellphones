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
 * [Nguyễn Duy Khang - 4.2.1 → 4.2.11] AuthController
 * Xử lý toàn bộ luồng xác thực & quản lý User.
 * LUỒNG XÁC THỰC (Sanctum):
 *   Đăng ký/Đăng nhập → trả token → Frontend lưu vào localStorage
 *   → Mỗi request sau đính kèm token vào Header: Authorization: Bearer {token}
 *   → Laravel Sanctum xác minh token → inject Auth::user() vào Controller
 */
class AuthController extends Controller
{
    protected $authService; // Tầng Business Logic cho User & Auth

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService; // Laravel DI tự inject
    }

    // ===================== [Nguyễn Duy Khang - 4.2.5] ĐĂNG KÝ =====================
    /**
     * POST /register → validate → AuthService@register → tạo User → trả token Sanctum
     * Sau khi thành công: Frontend lưu token + user vào localStorage → tự động đăng nhập
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users', // Email phải là duy nhất
            'password' => 'required|string|min:8|max:255|confirmed|not_regex:/\s/',
        ], [
            'password.not_regex' => 'Mật khẩu không được chứa khoảng trắng.',
        ]);

        $user = $this->authService->register($request->all());
        return response()->json([
            'data' => [
                'user'  => (new UserResource($user))->resolve(),
                'token' => $user->createToken('auth_token')->plainTextToken, // Tạo Sanctum token
            ],
            'message' => 'Đăng ký thành công!'
        ], 201);
    }

    // ===================== [Nguyễn Duy Khang - 4.2.6 & 4.2.7] ĐĂNG NHẬP & CHỐNG BRUTE FORCE =====================
    /**
     * POST /login → validate → AuthService@login (kiểm tra sai quá 5 lần → khóa tạm) → trả token
     * Throws: 401 nếu sai mật khẩu | 422 nếu bị khóa do brute force
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
                'token' => $result['token'], // Sanctum token gửi về cho Frontend
                'message' => 'Đăng nhập thành công!'
            ]);
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 401);
        }
    }

    // POST /logout → xóa token Sanctum hiện tại khỏi DB → token cũ không dùng được nữa
    public function logout(Request $request)
    {
        $user = Auth::user();
        if ($user) {
            /** @var \Laravel\Sanctum\HasApiTokens $user */
            $user->currentAccessToken()->delete(); // Vô hiệu hóa token đang dùng
        }
        return response()->json(['message' => 'Đã đăng xuất.']);
    }

    // GET /me → trả thông tin user hiện tại (dùng khi frontend F5 để khôi phục session)
    public function me()
    {
        return (new UserResource(Auth::user()))->resolve();
    }

    // ===================== [Nguyễn Duy Khang - 4.2.3] DANH SÁCH USER (ADMIN) =====================
    /** GET /admin/users → lọc + tìm kiếm + phân trang qua AuthService */
    public function index(Request $request)
    {
        $this->authorizeAdmin();
        $users = $this->authService->getAllUsers($request->all());
        return UserResource::collection($users);
    }

    // ===================== [Nguyễn Duy Khang - 4.2.1] THÊM USER (ADMIN) =====================
    /** POST /admin/users → validate → AuthService@createUser → hash password → INSERT bảng users */
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

    // ===================== [Nguyễn Duy Khang - 4.2.2] SỬA USER (ADMIN) =====================
    /** PUT /admin/users/{id} → validate → AuthService@updateUser → UPDATE bảng users */
    public function updateUser(Request $request, User $user)
    {
        $this->authorizeAdmin();
        $request->validate([
            'name'     => 'nullable|string|max:255',
            'email'    => 'nullable|email|unique:users,email,' . $user->id, // Cho phép giữ email cũ
            'phone'    => 'nullable|string|max:15',
            'address'  => 'nullable|string|max:500',
            'role'     => 'nullable|in:admin,user',
            'password' => 'nullable|string|min:8',
        ]);

        if ($request->has('updated_at') && $request->input('updated_at')) {
            $clientTime = \Carbon\Carbon::parse($request->input('updated_at'));
            if ($user->updated_at && $user->updated_at->gt($clientTime)) {
                return response()->json(['message' => 'Dữ liệu đã bị thay đổi ở tab khác, vui lòng tải lại!'], 409);
            }
        }

        $updatedUser = $this->authService->updateUser($user, $request->all());
        return response()->json(['message' => 'Cập nhật thành công!', 'user' => $updatedUser]);
    }

    // ===================== [Nguyễn Duy Khang - 4.2.4] KHÓA / MỞ KHÓA / XÓA USER (ADMIN) =====================

    /** POST /admin/users/{id}/lock → set is_locked=true → user không thể đăng nhập
     *  Throws: 422 nếu cố khóa tài khoản Admin */
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

    /** POST /admin/users/{id}/unlock → set is_locked=false → user đăng nhập được trở lại */
    public function unlock(User $user)
    {
        $this->authorizeAdmin();
        $updatedUser = $this->authService->unlockUser($user);
        return response()->json(['message' => 'Đã mở khóa tài khoản.', 'user' => $updatedUser]);
    }

    /** DELETE /admin/users/{id} → AuthService@deleteUser → xóa (từ chối nếu còn đơn hàng) */
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

    // ===================== [Nguyễn Duy Khang - 4.2.11] QUÊN MẬT KHẨU (OTP) =====================
    /**
     * POST /forgot-password:
     *   1. Tạo OTP 6 chữ số ngẫu nhiên
     *   2. Hash::make(OTP) → lưu vào bảng password_reset_tokens (không lưu plaintext)
     *   3. Gửi email HTML chứa OTP → user nhận được mã để đặt lại mật khẩu
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại trong hệ thống.'], 404);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT); // OTP 6 chữ số

        // Lưu OTP vào DB (hashed → bảo mật, không thể đọc ngược)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token'      => Hash::make($otp),
                'created_at' => Carbon::now()
            ]
        );

        // Gửi email HTML chứa OTP cho user
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

    // ===================== ĐẶT LẠI MẬT KHẨU =====================
    /**
     * POST /reset-password:
     *   1. Lấy record OTP từ password_reset_tokens theo email
     *   2. Hash::check(otp_user_nhap, token_trong_db) → xác minh đúng/sai
     *   3. Kiểm tra còn hạn 15 phút không
     *   4. Hash::make(new_password) → UPDATE users.password
     *   5. Xóa OTP (chỉ dùng được 1 lần)
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

        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        // Hash::check so sánh OTP user nhập với OTP đã hash trong DB
        if (!$record || !Hash::check($request->otp, $record->token)) {
            return response()->json(['message' => 'Mã OTP không chính xác.'], 422);
        }

        // Kiểm tra hạn 15 phút
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]); // Lưu mật khẩu mới đã hash

        // Xóa OTP sau khi dùng
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.']);
    }

    /** Từ chối nếu người dùng hiện tại không phải Admin → abort 403 */
    private function authorizeAdmin()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Yêu cầu quyền quản trị.');
        }
    }
}
