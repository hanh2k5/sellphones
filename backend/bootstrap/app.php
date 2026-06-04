<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(\App\Http\Middleware\SetLocaleMiddleware::class);
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $model = class_basename($e->getModel());
                $friendlyNames = [
                    'Category' => 'Danh mục',
                    'Product' => 'Sản phẩm không tồn tại hoặc đã bị xóa vĩnh viễn',
                    'Order' => 'Đơn hàng',
                    'User' => 'Người dùng',
                    'Review' => 'Đánh giá',
                    'Voucher' => 'Voucher',
                    'CartItem' => 'Sản phẩm trong giỏ hàng'
                ];
                $name = $friendlyNames[$model] ?? $model;
                // Product message is already the full sentence, others use the pattern below
                if ($model === 'Product') {
                    return response()->json(['message' => $name . '.'], 404);
                }
                return response()->json([
                    'message' => "Không tìm thấy $name hoặc dữ liệu không tồn tại."
                ], 404);
            }
        });
    })->create();
