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
 * MỤC: 4.1.5, 4.1.8, 4.1.9 (QUẢN LÝ ĐƠN HÀNG)
 */
class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * [Phan Đình Hạnh - 4.1.7] Hiển thị danh sách đơn hàng (Phân trang + Filter)
     */
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Order::with(['items.product' => fn($q) => $q->withTrashed(), 'voucher']);

        $isAdminRoute = $request->is('api/admin/*');

        if ($isAdminRoute && $user->isAdmin()) {
            $query->with('user:id,name,email');
        } else {
            $query->where('user_id', $user->id);
        }

        $query->filter([
            'status' => $request->status,
            'search' => $request->search,
            'product_id' => $request->product_id
        ]);

        $orders = $query->orderBy('created_at', 'desc')->paginate(4);
        return OrderResource::collection($orders);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Không có quyền truy cập.'], 403);
        }

        $order->load(['items.product' => fn($q) => $q->withTrashed(), 'voucher', 'user:id,name,email']);
        return (new OrderResource($order))->resolve();
    }

    /**
     * [Phan Đình Hạnh - 4.1.4] Đặt hàng (Checkout)
     */
    public function store(Request $request)
    {
        try {
            $order = $this->orderService->createOrder($request->all(), $request->user());
            return response()->json([
                'order' => (new OrderResource($order))->resolve(),
                'message' => 'Đặt hàng thành công!'
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * [Phan Đình Hạnh - 4.1.8] Duyệt đơn hàng (Admin)
     */
    public function approve(Request $request, Order $order)
    {
        try {
            $order = $this->orderService->approveOrder($order, $request->updated_at);
            return response()->json(['message' => 'Đã duyệt đơn hàng!', 'order' => $order]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function complete(Request $request, Order $order)
    {
        try {
            $order = $this->orderService->completeOrder($order, $request->updated_at);
            return response()->json(['message' => 'Đã hoàn tất đơn hàng thành công!', 'order' => $order]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * [Phan Đình Hạnh - 4.1.9] Hủy đơn hàng (User/Admin)
     */
    public function cancel(Request $request, Order $order)
    {
        try {
            $order = $this->orderService->cancelOrder($order, $request->user(), $request->updated_at);
            return response()->json(['message' => 'Đã hủy đơn hàng!', 'order' => $order]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function destroy(Order $order)
    {
        if (in_array($order->status, ['pending', 'confirmed', 'processing'])) {
            return response()->json(['message' => 'Không thể xóa đơn hàng đang xử lý.'], 422);
        }
        $order->delete();
        return response()->json(['message' => 'Đã xóa đơn hàng.']);
    }

    /**
     * [Phan Đình Hạnh - 4.1.14] Xác nhận thanh toán (MoMo/ATM)
     */
    public function markAsPaid(Request $request, Order $order)
    {
        $user = $request->user();
        if (!$user->isAdmin() && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Không có quyền truy cập.'], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Đơn hàng đã được xử lý.'], 422);
        }

        $order->update(['payment_status' => 'paid']);
        return response()->json(['message' => 'Thanh toán thành công!', 'order' => $order]);
    }

    public function checkStatus(Order $order)
    {
        $user = request()->user();
        if (!$user->isAdmin() && $order->user_id !== $user->id) {
            return response()->json(['message' => 'Không có quyền truy cập.'], 403);
        }

        $cached = cache()->get("order_status_{$order->id}");
        return response()->json([
            'changed'    => (bool) $cached,
            'status'     => $order->refresh()->status,
            'message'    => $cached['message'] ?? null,
            'updated_at' => $order->updated_at->toIso8601String(),
        ]);
    }
}
