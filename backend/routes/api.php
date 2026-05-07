<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- AUTHENTICATION (Khang's 4 features) ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --- ONLY PRODUCT DISPLAY (Ha's simplified feature) ---
Route::get('/products', [ProductController::class, 'index']);

// --- PROTECTED ROUTES ---
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin status check (Khang's RBAC)
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/status', function () {
            return response()->json(['message' => 'Chào mừng Admin!']);
        });
    });
});

// Status endpoint
Route::get('/status', function () { 
    return response()->json(['status' => 'Core Systems Active', 'version' => '1.5']); 
});
