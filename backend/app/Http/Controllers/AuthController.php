<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

/**
 * SV THỰC HIỆN: NGUYỄN DUY KHANG
 * MỤC: 4.2.5 - ĐĂNG KÝ TÀI KHOẢN
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
            'address'  => 'nullable|string|max:255',
            'phone'    => 'nullable|string|max:15',
        ]);

        $user = $this->authService->register($request->all());
        
        return (new UserResource($user))
            ->additional(['message' => 'Đăng ký thành công!']);
    }
}