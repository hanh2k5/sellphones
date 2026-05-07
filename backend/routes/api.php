<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// [Nguyễn Duy Khang - 4.2.5] Đăng ký
Route::post('/register', [AuthController::class, 'register']);

// [Nguyễn Duy Khang - 4.2.6] Đăng nhập
Route::post('/login', [AuthController::class, 'login']);

// Yêu cầu đăng nhập (Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// [4.2.1] Route dành cho ADMIN
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::get('/admin/status', function () {
        return response()->json(['message' => 'Chào mừng Admin! Bạn có quyền truy cập vùng này.']);
    });
});

// Status endpoint
Route::get('/status', function () { 
    return response()->json(['status' => 'Auth Ready', 'version' => '1.0']); 
});
