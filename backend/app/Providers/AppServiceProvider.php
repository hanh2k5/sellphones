<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Chống Spam Đăng nhập/Đăng ký (10 requests / 1 phút / 1 IP, tắt ở môi trường local/testing để tránh lỗi chạy test E2E)
        RateLimiter::for('auth', function (Request $request) {
            if (app()->environment('local', 'testing')) {
                return Limit::none();
            }
            return Limit::perMinute(10)->by($request->ip());
        });

        // Chống Spam Đặt hàng (5 requests / 1 phút / 1 User, tắt ở môi trường local/testing để tránh lỗi chạy test E2E)
        RateLimiter::for('orders', function (Request $request) {
            if (app()->environment('local', 'testing')) {
                return Limit::none();
            }
            return Limit::perMinute(5)->by($request->user()?->id ?: $request->ip());
        });

        // Chống Spam API chung (60 requests / 1 phút, tắt ở môi trường local/testing để tránh lỗi chạy test E2E)
        RateLimiter::for('api', function (Request $request) {
            if (app()->environment('local', 'testing')) {
                return Limit::none();
            }
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
