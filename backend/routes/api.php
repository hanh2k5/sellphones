<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\AdminController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- AUTHENTICATION (Nguyễn Duy Khang) ---
// Báo cáo 4.2.5: Đăng ký tài khoản (Register)
Route::post('/register', [AuthController::class, 'register']);
// Báo cáo 4.2.6 & 4.2.7: Đăng nhập hệ thống & Giới hạn đăng nhập sai
Route::post('/login', [AuthController::class, 'login']);

// --- SẢN PHẨM (Đặng Văn Hà) ---
// Báo cáo 4.3.8: Hiển thị danh sách sản phẩm (Phân trang và Tìm kiếm)
Route::get('/products', [ProductController::class, 'index']);
// Báo cáo 4.3.9: Hiển thị chi tiết sản phẩm (Eager Loading & Multimedia)
Route::get('/products/{product}', [ProductController::class, 'show']);

// --- DANH MỤC (Đặng Văn Hà) ---
// Báo cáo 4.3.4: Hiển thị danh sách danh mục (Cấu trúc cây)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/flat', [CategoryController::class, 'flat']);

// --- BẢO VỆ ROUTE (Nguyễn Duy Khang) ---
// Báo cáo 4.2.8: Phân quyền Admin/User (Middleware)
Route::middleware('auth:sanctum')->group(function () {
    // Báo cáo 4.2.9: Cập nhật hồ sơ cá nhân
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Route dành riêng cho Admin (Báo cáo 4.2.8)
    Route::middleware('admin')->prefix('admin')->group(function () {
        // DASHBOARD
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // QUẢN LÝ ĐƠN HÀNG (Phan Đình Hạnh)
        Route::get('/orders', [OrderController::class, 'adminIndex']);
        // Báo cáo 4.1.8: Duyệt đơn hàng và Xử lý tranh chấp dữ liệu (Optimistic Locking)
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::post('/orders/{order}/confirm', [OrderController::class, 'confirmOrder']);
        Route::post('/orders/{order}/complete', [OrderController::class, 'completeOrder']);
    });

    // --- GIỎ HÀNG (Phan Đình Hạnh) ---
    Route::get('/cart', [CartController::class, 'index']);
    // Báo cáo 4.1.1 & 4.1.2: Thêm sản phẩm vào giỏ hàng & Kiểm tra tồn kho
    Route::post('/cart', [CartController::class, 'store']);
    Route::put('/cart/{id}', [CartController::class, 'update']);
    // Báo cáo 4.1.3: Xóa sản phẩm khỏi giỏ hàng
    Route::delete('/cart/{id}', [CartController::class, 'destroy']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // --- ĐƠN HÀNG (Phan Đình Hạnh) ---
    // Báo cáo 4.1.6: Hiển thị danh sách đơn hàng
    Route::get('/orders', [OrderController::class, 'index']);
    // Báo cáo 4.1.5: Tạo đơn hàng mới (Order Creation & Transaction)
    Route::post('/orders', [OrderController::class, 'store']);
    // Báo cáo 4.1.7: Hiển thị chi tiết đơn hàng
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    // Báo cáo 4.1.14: Thanh toán qua cổng ví điện tử (Fake MoMo UI)
    Route::post('/orders/{order}/confirm-payment', [OrderController::class, 'confirmPayment']);
    // Báo cáo 4.1.9: Hủy đơn hàng và Hoàn tồn kho tự động
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    // --- VOUCHERS (Phan Đình Hạnh) ---
    // Báo cáo 4.1.13: Thêm mã giảm giá (Voucher) vào đơn hàng
    Route::get('/vouchers', [VoucherController::class, 'index']);
    Route::post('/vouchers/apply', [VoucherController::class, 'apply']);
});
