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
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReviewController;

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
Route::get('/products/{product}/reviews', [ReviewController::class, 'index']);

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
        // QUẢN LÝ NGƯỜI DÙNG (Nguyễn Duy Khang)
        Route::get('/users', [AuthController::class, 'index']);
        Route::post('/users', [AuthController::class, 'storeUser']);
        Route::put('/users/{user}', [AuthController::class, 'updateUser']);
        Route::delete('/users/{user}', [AuthController::class, 'destroyUser']);
        Route::post('/users/{user}/unlock', [AuthController::class, 'unlock']);
        Route::post('/users/{user}/lock', [AuthController::class, 'lock']);
        // QUẢN LÝ ĐƠN HÀNG (Phan Đình Hạnh)
        Route::get('/orders', [OrderController::class, 'adminIndex']);
        // Báo cáo 4.1.8: Duyệt đơn hàng và Xử lý tranh chấp dữ liệu (Optimistic Locking)
        Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::post('/orders/{order}/confirm', [OrderController::class, 'confirmOrder']);
        Route::post('/orders/{order}/complete', [OrderController::class, 'completeOrder']);
        // Báo cáo 4.1.10: Xóa vĩnh viễn đơn hàng
        Route::delete('/orders/{order}', [OrderController::class, 'destroy']);

        // QUẢN LÝ DANH MỤC (Đặng Văn Hà)
        // Báo cáo 4.3.1: Thêm mới danh mục sản phẩm (Categories)
        Route::post('/categories', [CategoryController::class, 'store']);
        // Báo cáo 4.3.2: Cập nhật danh mục sản phẩm
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        // Báo cáo 4.3.3: Xóa danh mục sản phẩm
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // QUẢN LÝ SẢN PHẨM (Đặng Văn Hà)
        // Báo cáo 4.3.5: Thêm mới sản phẩm
        Route::post('/products', [ProductController::class, 'store']);
        // Báo cáo 4.3.6: Cập nhật sản phẩm
        Route::put('/products/{product}', [ProductController::class, 'update']);
        // Báo cáo 4.3.7: Xóa mềm sản phẩm
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);
        // Báo cáo 4.3.14: Thùng rác sản phẩm
        Route::get('/products/trash', [ProductController::class, 'trash']);
        Route::post('/products/{id}/restore', [ProductController::class, 'restore']);
        Route::delete('/products/{id}/force-delete', [ProductController::class, 'forceDelete']);
        // Báo cáo 4.3.15: Quản lý ảnh (Gallery)
        Route::post('/products/{product}/images', [ProductController::class, 'uploadImages']);
        Route::delete('/products/{id}/images/{imageId}', [ProductController::class, 'deleteImage']);
        // Upload file chung & Check update
        Route::post('/upload', [ProductController::class, 'uploadFile']);
        Route::get('/products/{id}/check-updated', [ProductController::class, 'checkUpdated']);

        // QUẢN LÝ ĐÁNH GIÁ (Admin)
        Route::get('/reviews', [ReviewController::class, 'adminIndex']);
        Route::put('/reviews/{review}/moderate', [ReviewController::class, 'moderate']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
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

    // AI Chatbot [Phan Đình Hạnh - 4.1.11]
    Route::get('/ai/chats', [ChatController::class, 'index']);
    Route::post('/ai/chats', [ChatController::class, 'send']);
    Route::delete('/ai/chats', [ChatController::class, 'clear']);

    // ĐÁNH GIÁ SẢN PHẨM (User)
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
});
