<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Http\Requests\StoreOrderRequest;
use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\OrderResource;

/**
 * SV THỰC HIỆN: PHAN ĐÌNH HẠNH
 * MỤC: 4.1.4, 4.1.7, 4.1.8, 4.1.9 (QUẢN LÝ ĐƠN HÀNG)
 */
class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * [Phan Đình Hạnh - 4.1.7] Hiển thị danh sách đơn hàng
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Order::with(['items.product' => fn($q) => $q->withTrashed(), 'voucher']);

        if ($request->is('api/admin/*') && $user->isAdmin()) {
            $query->with('user:id,name,email');
        } else {
            $query->where('user_id', $user->id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);
        return OrderResource::collection($orders);
    }

    /**
     * [Phan Đình Hạnh - 4.1.4] Đặt hàng (Checkout)
     */
    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder($request->validated(), $request->user());
            return response()->json([
                'order' => (new OrderResource($order))->resolve(),
                'message' => 'Đặt hàng thành công!'
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Bạn không có quyền xem đơn hàng này.'], 403);
        }

        $order->load(['items.product' => fn($q) => $q->withTrashed(), 'voucher', 'user:id,name,email']);
        return (new OrderResource($order))->resolve();
    }
}
