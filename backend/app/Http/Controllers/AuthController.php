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
 * MỤC: 4.2.1 -> 4.2.8 (XÁC THỰC & BẢO MẬT)
 */
class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * [Nguyễn Duy Khang - 4.2.5] Đăng ký tài khoản
     */
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'address'  => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:15',
        ]);

        $user = $this->authService->register($request->all());
        
        return (new UserResource($user))
            ->additional(['message' => 'Đăng ký thành công!']);
    }

    /**
     * [Nguyễn Duy Khang - 4.2.6] Đăng nhập hệ thống
     * [Nguyễn Duy Khang - 4.2.7] Chống brute-force
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
     * [Nguyễn Duy Khang - 4.2.11] Quên mật khẩu
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Email không tồn tại trong hệ thống.'], 404);
        }

        $token = Str::random(64);

        try {
            Mail::to($user->email)->send(new ResetPasswordMail($token, $user));
            return response()->json([
                'message' => 'Mã khôi phục mật khẩu đã được gửi vào email của bạn.',
                'reset_token' => $token
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Không thể gửi email: ' . $e->getMessage()], 500);
        }
    }
}