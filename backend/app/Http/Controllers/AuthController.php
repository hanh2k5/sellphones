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
            'password' => 'required|string|min:8|max:255|confirmed',
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
     * [Nguyễn Duy Khang - 4.2.11] Quên mật khẩu (Logic Fake)
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại trong hệ thống.'], 404);
        }

        // Tạo token ngẫu nhiên
        $token = Str::random(64);

        // Lưu vào database password_reset_tokens
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            [
                'token' => $token,
                'created_at' => Carbon::now()
            ]
        );

        return response()->json([
            'message' => 'Mã khôi phục mật khẩu đã được tạo thành công (Chế độ giả lập).',
            'reset_token' => $token
        ]);
    }

    /**
     * [Nguyễn Duy Khang - 4.2.11] Đặt lại mật khẩu (Logic Fake)
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.min' => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại trong hệ thống.'], 404);
        }

        // Kiểm tra token
        $record = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Liên kết đặt lại mật khẩu không hợp lệ.'], 422);
        }

        // Kiểm tra thời hạn token (15 phút)
        $createdAt = Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(15)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Liên kết đặt lại mật khẩu đã hết hạn.'], 422);
        }

        // Cập nhật mật khẩu mới và băm mật khẩu
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Vô hiệu hóa token sau khi dùng
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
