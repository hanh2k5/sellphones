<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuthService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Exception;

/**
 * SV THỰC HIỆN: NGUYỄN DUY KHANG
 * MỤC: 4.2.1 -> 4.2.7 (XÁC THỰC & BẢO MẬT)
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
     * [Nguyễn Duy Khang - 4.2.7] CHỐNG BRUTE-FORCE (GIỚI HẠN ĐĂNG NHẬP)
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
            $user = User::where('email', $request->email)->first();
            $retryAfter = null;

            if ($user?->locked_until && Carbon::now()->lessThan($user->locked_until)) {
                $retryAfter = Carbon::now()->diffInSeconds($user->locked_until);
            }

            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
                'attempts_left' => $user ? max(0, 5 - (int) $user->login_attempts) : null,
                'locked' => (bool) $retryAfter,
                'retry_after' => $retryAfter,
            ], 422);
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
}
