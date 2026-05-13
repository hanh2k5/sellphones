<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class AdminController extends Controller
{
    public function dashboard(): JsonResponse
    {
        $stats = [
            // Doanh thu: Chỉ tính đơn 'completed' của User thường (không tính Admin đặt test)
            'totalRevenue'  => Order::where('status', 'completed')
                ->whereHas('user', fn($q) => $q->where('role', '!=', 'admin'))
                ->sum('total_amount'),
            
            // Tổng đơn: Cũng chỉ tính đơn của khách hàng thật
            'totalOrders'   => Order::whereHas('user', fn($q) => $q->where('role', '!=', 'admin'))->count(),
            
            'totalUsers'    => User::where('role', 'user')->count(),
            'lowStockCount' => Product::where('stock', '<=', 5)->count(),
            'pendingOrders' => Order::where('status', 'pending')->count(),
        ];

        return response()->json(['stats' => $stats]);
    }
}
