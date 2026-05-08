<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;

/**
 * SV THỰC HIỆN: PHAN ĐÌNH HẠNH
 * MỤC: 4.1 (TỔNG QUAN HỆ THỐNG)
 */
class DashboardController extends Controller
{
    public function index()
    {
        // 1. Thống kê tổng quan
        $stats = [
            'totalUsers'    => User::count(),
            'totalOrders'   => Order::whereHas('user', fn($q) => $q->where('role', '!=', 'admin'))->count(),
            'totalRevenue'  => (float) Order::where('status', 'completed')
                                ->whereHas('user', fn($q) => $q->where('role', '!=', 'admin'))
                                ->sum('total_amount'),
            'lowStockCount' => Product::where('stock', '<=', 5)->count(),
        ];

        // 2. Dữ liệu biểu đồ doanh thu 7 ngày gần nhất
        $labels = [];
        $revenueData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $labels[] = now()->subDays($i)->format('d/m');
            $revenueData[] = (float) Order::where('status', 'completed')
                ->whereHas('user', fn($q) => $q->where('role', '!=', 'admin'))
                ->whereDate('created_at', $date)
                ->sum('total_amount');
        }

        // 3. Thống kê trạng thái đơn hàng (Dữ liệu biểu đồ tròn)
        $statusData = [
            'pending'    => Order::where('status', 'pending')->count(),
            'shipping'   => Order::where('status', 'shipping')->count(),
            'completed'  => Order::where('status', 'completed')->count(),
            'cancelled'  => Order::where('status', 'cancelled')->count(),
        ];

        return response()->json([
            'stats'         => $stats,
            'chartData'     => [
                'labels' => $labels,
                'data'   => $revenueData,
            ],
            'statusData'    => $statusData,
            'recent_orders' => Order::with('user')->orderBy('created_at', 'desc')->limit(5)->get(),
        ]);
    }
}
