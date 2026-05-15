<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Exception;

/**
 * SV THỰC HIỆN: NGUYỄN DUY KHANG
 * MỤC: 4.2.1 -> 4.2.8 (XÁC THỰC, BẢO MẬT & QUẢN LÝ USER)
 */
class AuthController extends Controller
{
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
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $this->authService->register($request->all());
        return (new UserResource($user))
            ->additional(['message' => 'Đăng ký thành công!']);
    }

    /**
     * [Nguyễn Duy Khang - 4.2.6] Đăng nhập hệ thống
     * [Nguyễn Duy Khang - 4.2.7] Giới hạn đăng nhập sai (Brute Force)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
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
     * Admin: Quản lý người dùng
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

    public function unlock(User $user)
    {
        $this->authorizeAdmin();
        $updatedUser = $this->authService->unlockUser($user);
        return response()->json(['message' => 'Đã mở khóa tài khoản.', 'user' => $updatedUser]);
    }

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

        // Tạo token ngẫu nhiên (Trong thực tế nên lưu vào bảng password_resets)
        $token = Str::random(64);

        // Gửi mail thật
        try {
            Mail::to($user->email)->send(new ResetPasswordMail($token, $user));
            
            return response()->json([
                'message' => 'Mã khôi phục mật khẩu đã được gửi vào email của bạn.',
                'reset_token' => $token // Vẫn trả về token để FE tiện xử lý nếu cần
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Không thể gửi email: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'token'    => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Thông tin không hợp lệ.'], 422);
        }

        // Cập nhật mật khẩu (Logic thực tế nhưng bỏ qua kiểm tra token thật)
        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password)
        ]);

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