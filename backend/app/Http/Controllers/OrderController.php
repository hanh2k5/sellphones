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
     * [Phan Đình Hạnh - 4.1.7] Hiển thị danh sách đơn hàng (Phân trang)
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $user = $request->user();
        // Eager loading để tối ưu hiệu năng (N+1 query)
        $query = Order::with(['items.product' => fn($q) => $q->withTrashed(), 'voucher']);

        // Phân quyền: Admin xem tất cả, User xem đơn của mình
        if ($request->is('api/admin/*') && $user->isAdmin()) {
            $query->with('user:id,name,email');
        } else {
            $query->where('user_id', $user->id);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);
        return OrderResource::collection($orders);
    }

    /**
     * [Phan Đình Hạnh - 4.1.4] Xử lý đặt hàng (Checkout)
     * @param StoreOrderRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreOrderRequest $request)
    {
        try {
            // Ủy thác xử lý logic phức tạp cho OrderService (Clean Architecture)
            $order = $this->orderService->createOrder($request->validated(), $request->user());
            return response()->json([
                'order' => (new OrderResource($order))->resolve(),
                'message' => __('messages.order_success')
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * [Phan Đình Hạnh - 4.1.8] Chi tiết đơn hàng
     * @param Request $request
     * @param Order $order
     * @return array
     */
    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        // Kiểm tra quyền sở hữu đơn hàng
        if (!$user->isAdmin() && $order->user_id !== $user->id) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $order->load(['items.product' => fn($q) => $q->withTrashed(), 'voucher', 'user:id,name,email']);
        return (new OrderResource($order))->resolve();
    }

    /**
     * [Phan Đình Hạnh - 4.1.14] Mô phỏng xác nhận thanh toán điện tử MoMo
     * Đảm bảo quy trình thanh toán không bị đứt gãy trong môi trường demo.
     * @param Request $request
     * @param Order $order
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmPayment(Request $request, Order $order)
    {
        $user = $request->user();
        
        // Xác thực quyền sở hữu giao dịch
        if ($order->user_id !== $user->id) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        // Chống lặp lại thanh toán (Idempotency)
        if ($order->payment_status === 'paid') {
            return response()->json(['message' => __('messages.already_paid')], 422);
        }

        // Cập nhật trạng thái an toàn
        $order->update([
            'payment_status' => 'paid',
            'status' => 'pending' // Giữ chờ xác nhận để Admin kiểm tra lần cuối
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.momo_success')
        ]);
    }
}
