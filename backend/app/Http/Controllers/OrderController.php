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
        $query = Order::with(['items.product' => fn($q) => $q->withTrashed(), 'voucher'])
            ->filter($request->all());

        // Phân quyền: Admin xem tất cả, User xem đơn của mình
        if ($request->is('api/admin/*') && $user->isAdmin()) {
            $query->with('user:id,name,email');
        } else {
            $query->where('user_id', $user->id);
        }

        $perPage = $request->input('per_page', 10); // Mặc định về 10 theo yêu cầu của bạn
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);
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

    /**
     * [Phan Đình Hạnh - 4.1.8] Duyệt đơn hàng & Xử lý tranh chấp (Optimistic Locking)
     */
    public function updateStatus(Request $request, Order $order)
    {
        // 1. Chỉ Admin mới được phép duyệt
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        $request->validate([
            'status' => 'required|string|in:pending,confirmed,shipping,shipped,cancelled',
            'last_updated_at' => 'required|string' // Client gửi ISO8601 của updated_at lúc họ LOAD trang
        ]);

        // 2. LOGIC TRẢNH CHẤP (2-Tab Logic)
        // So sánh timestamp (với độ trễ nhỏ do làm tròn nếu cần, nhưng Carbon toIso8601String là chuẩn nhất)
        $clientTime = $request->last_updated_at;
        $serverTime = $order->updated_at->toIso8601String();

        if ($clientTime !== $serverTime) {
            return response()->json([
                'success' => false,
                'message' => __('messages.order_conflict'),
                'current_data' => new OrderResource($order)
            ], 409); // 409 Conflict
        }

        // 3. Cập nhật trạng thái
        $order->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.update_success'),
            'order'   => new OrderResource($order)
        ]);
    }

    /**
     * [Phan Đình Hạnh - Báo cáo 4.1.6] Danh sách đơn hàng cho Admin
     * Lấy danh sách kèm theo thông tin User và Sản phẩm (kể cả sản phẩm đã xóa mềm - withTrashed).
     */
    public function adminIndex(Request $request)
    {
        $query = Order::with(['items.product' => fn($q) => $q->withTrashed(), 'voucher', 'user:id,name,email'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', fn($q) => $q->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%"));
        }

        $perPage = $request->input('per_page', 10); // Mặc định về 10 theo yêu cầu của bạn
        $orders = $query->paginate($perPage);
        return OrderResource::collection($orders);
    }

    /**
     * [Phan Đình Hạnh - Báo cáo 4.1.8] Duyệt đơn hàng (pending → shipping)
     * Kỹ thuật: Optimistic Locking so sánh updated_at để ngăn chặn lỗi 2 tab Admin cùng thao tác.
     */
    public function confirmOrder(Request $request, Order $order)
    {
        $request->validate(['updated_at' => 'required|string']);

        if ($request->updated_at !== $order->updated_at->toIso8601String()) {
            return response()->json(['message' => __('messages.order_conflict')], 409);
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Đơn hàng không ở trạng thái chờ xử lý.'], 422);
        }

        $order->update(['status' => 'shipping']);
        return response()->json(['success' => true, 'message' => __('messages.update_success'), 'order' => new OrderResource($order)]);
    }

    /**
     * [Admin] Hoàn thành đơn (shipping → completed) với Optimistic Locking
     */
    public function completeOrder(Request $request, Order $order)
    {
        $request->validate(['updated_at' => 'required|string']);

        if ($request->updated_at !== $order->updated_at->toIso8601String()) {
            return response()->json(['message' => __('messages.order_conflict')], 409);
        }

        if ($order->status !== 'shipping') {
            return response()->json(['message' => 'Đơn hàng không ở trạng thái đang giao.'], 422);
        }

        $order->update(['status' => 'completed']);
        return response()->json(['success' => true, 'message' => __('messages.update_success'), 'order' => new OrderResource($order)]);
    }

    /**
     * [Phan Đình Hạnh - 4.1.9] Hủy đơn hàng và Hoàn tồn kho tự động
     */
    public function cancel(Request $request, Order $order)
    {
        try {
            $updatedOrder = $this->orderService->cancelOrder($order, $request->user(), $request->updated_at);
            return response()->json([
                'success' => true,
                'message' => __('messages.update_success'),
                'order'   => new OrderResource($updatedOrder)
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
    /**
     * [Phan Đình Hạnh - 4.1.10] Xóa vĩnh viễn đơn hàng
     * Ràng buộc logic tuyệt đối: Chỉ cho phép thực hiện lệnh xóa nếu status đang là cancelled (Đã hủy).
     */
    public function destroy(Order $order)
    {
        if ($order->status !== 'cancelled') {
            return response()->json([
                'message' => 'Chỉ có thể xóa vĩnh viễn các đơn hàng đã bị hủy.'
            ], 422);
        }

        $order->delete(); // Do đã có cascadeOnDelete nên sẽ tự xóa order_items

        return response()->json([
            'success' => true,
            'message' => __('messages.order_deleted')
        ]);
    }
}
