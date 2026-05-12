<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VoucherController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- AUTHENTICATION (Bảo mật 4 tính năng của Khang) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- ONLY PRODUCT DISPLAY (Tính năng của Hà) ---
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// --- CATEGORIES (Tính năng của Hà) ---
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/flat', [CategoryController::class, 'flat']);

// --- PROTECTED ROUTES ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Route dành riêng cho Admin (Khang's RBAC)
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/status', function () {
            return response()->json(['message' => 'Chào mừng Admin!']);
        });
    });

    // --- CART (Phan Đình Hạnh - 4.1.1 -> 4.1.3) ---
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // --- ORDERS (Phan Đình Hạnh - 4.1.4 -> 4.1.9) ---
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment']);

    // --- VOUCHERS (Phan Đình Hạnh - 4.1.13) ---
    Route::get('/vouchers', [VoucherController::class, 'index']);
    Route::post('/vouchers/apply', [VoucherController::class, 'apply']);
});

// Status endpoint
Route::get('/status', function () { 
    return response()->json(['status' => 'Core Systems Active', 'version' => '1.5']); 
});
