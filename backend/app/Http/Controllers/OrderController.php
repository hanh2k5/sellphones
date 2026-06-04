<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Http\Requests\StoreOrderRequest;
use Illuminate\Http\Request;
use Exception;
use App\Http\Resources\OrderResource;

/**
 * [Phan Đình Hạnh - 4.1.4, 4.1.7, 4.1.8, 4.1.9] OrderController
 * Nhận HTTP Request → uỷ thác cho OrderService → trả về JSON Response.
 */
class OrderController extends Controller
{
    protected $orderService; // Lưu instance OrderService để dùng trong các action

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService; // Laravel tự inject qua Dependency Injection
    }

    // ===================== [Phan Đình Hạnh - 4.1.7] DANH SÁCH ĐƠN HÀNG =====================

    /**
     * GET /orders hoặc /admin/orders – Lấy danh sách đơn hàng có phân trang.
     * Admin xem tất cả đơn, User chỉ xem đơn của mình.
     * Trả về: collection OrderResource kèm meta phân trang.
     */
    public function index(Request $request)
    {
        $user  = $request->user();
        // Eager Loading: lấy kèm items (kể cả SP đã xóa mềm) và voucher → tránh N+1 query
        $query = Order::with(['items.product' => fn($q) => $q->withTrashed(), 'voucher'])
            ->filter($request->all()); // Scope lọc theo status, search, ...

        // Phân quyền: Admin xem tất cả đơn + thông tin user; User chỉ xem đơn của mình
        if ($request->is('api/admin/*') && $user->isAdmin()) {
            $query->with('user:id,name,email');
        } else {
            $query->where('user_id', $user->id);
        }

        $perPage = $request->input('per_page', 10); // Số đơn/trang, mặc định 10
        $orders  = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return OrderResource::collection($orders);
    }

    // ===================== [Phan Đình Hạnh - 4.1.4] ĐẶT HÀNG (CHECKOUT) =====================

    /**
     * POST /orders – Tạo đơn hàng mới từ giỏ hàng hiện tại.
     * $request đã qua StoreOrderRequest validate (receiver_name, phone, shipping_address, ...).
     * Trả về: đơn hàng vừa tạo | 422 nếu giỏ rỗng / hết hàng.
     */
    public function store(StoreOrderRequest $request)
    {
        try {
            // Ủy thác toàn bộ logic phức tạp cho OrderService (Transaction, Locking, Voucher)
            $order = $this->orderService->createOrder($request->validated(), $request->user());
            return response()->json([
                'order'   => (new OrderResource($order))->resolve(),
                'message' => __('messages.order_success')
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // ===================== [Phan Đình Hạnh - 4.1.8] CHI TIẾT ĐƠN HÀNG =====================

    /**
     * GET /orders/{id} – Lấy chi tiết 1 đơn hàng.
     * Kiểm tra quyền sở hữu: chỉ Admin hoặc chủ đơn mới được xem.
     */
    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        // User không phải Admin và không phải chủ đơn → 403
        if (!$user->isAdmin() && $order->user_id !== $user->id) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }
        // Load quan hệ đầy đủ: items (kể cả SP đã xóa mềm), voucher, thông tin user
        $order->load(['items.product' => fn($q) => $q->withTrashed(), 'voucher', 'user:id,name,email']);
        return (new OrderResource($order))->resolve();
    }

    // ===================== [Phan Đình Hạnh - 4.1.14] XÁC NHẬN THANH TOÁN MOMO =====================

    /**
     * POST /orders/{id}/confirm-payment – Mô phỏng xác nhận thanh toán MoMo (demo).
     * Chỉ chủ đơn mới được gọi. Chống thanh toán trùng lặp (Idempotency).
     * Trả về: success | 422 nếu đã thanh toán hoặc đã hủy.
     */
    public function confirmPayment(Request $request, Order $order)
    {
        $user = $request->user();
        if ($order->user_id !== $user->id) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }
        // Chống gọi API thanh toán nhiều lần (Idempotency Guard)
        if ($order->status === 'cancelled') { return response()->json(['message' => 'Cancelled'], 422); }
        if ($order->payment_status === 'paid') {
            return response()->json(['message' => __('messages.already_paid')], 422);
        }
        $order->update([
            'payment_status' => 'paid',
            'status'         => 'pending' // Giữ pending để Admin kiểm tra và xác nhận lần cuối
        ]);
        return response()->json(['success' => true, 'message' => __('messages.momo_success')]);
    }

    // ===================== [Phan Đình Hạnh - 4.1.8] DUYỆT ĐƠN (OPTIMISTIC LOCKING) =====================

    /**
     * PUT /admin/orders/{id}/status – Cập nhật trạng thái đơn (chỉ Admin).
     * Dùng Optimistic Locking: so sánh last_updated_at client gửi với updated_at trong DB.
     * Trả về: đơn sau cập nhật | 409 nếu xung đột 2-tab.
     */
    public function updateStatus(Request $request, Order $order)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }
        $request->validate([
            'status'          => 'required|string|in:pending,confirmed,shipping,shipped,cancelled',
            'last_updated_at' => 'required|string' // Timestamp ISO8601 lúc client load trang
        ]);

        // Optimistic Locking: nếu timestamp client ≠ DB → có người khác vừa sửa → 409
        $clientTime = $request->last_updated_at;
        $serverTime = $order->updated_at->toIso8601String();
        if ($clientTime !== $serverTime) {
            return response()->json([
                'success'      => false,
                'message'      => __('messages.order_conflict'),
                'current_data' => new OrderResource($order) // Trả về dữ liệu mới nhất để client cập nhật
            ], 409);
        }

        $order->update(['status' => $request->status]);
        return response()->json(['success' => true, 'message' => __('messages.update_success'), 'order' => new OrderResource($order)]);
    }

    // ===================== [Phan Đình Hạnh - 4.1.6 & 4.1.8] ADMIN QUẢN LÝ ĐƠN =====================

    /**
     * GET /admin/orders – Danh sách đơn hàng cho Admin, kèm lọc theo status & tên/email user.
     */
    public function adminIndex(Request $request)
    {
        $query = Order::with(['items.product' => fn($q) => $q->withTrashed(), 'voucher', 'user:id,name,email'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status); // Lọc theo trạng thái đơn
        }
        if ($request->filled('search')) {
            $search = $request->search;
            // Tìm đơn theo tên/email user, hoặc mã đơn hàng, hoặc tên người nhận
            $query->where(function($q) use ($search) {
                $q->where('order_code', 'like', "%$search%")
                  ->orWhere('receiver_name', 'like', "%$search%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%$search%")
                                                   ->orWhere('email', 'like', "%$search%"));
            });
        }

        $perPage = $request->input('per_page', 10);
        $orders  = $query->paginate($perPage);
        return OrderResource::collection($orders);
    }

    /**
     * POST /admin/orders/{id}/confirm – Duyệt đơn: pending → shipping (có Optimistic Locking).
     * $request->updated_at: timestamp client giữ. Trả về: 409 nếu xung đột.
     */
    public function confirmOrder(Request $request, Order $order)
    {
        $request->validate(['updated_at' => 'required|string']);

        // Kiểm tra xung đột: nếu đơn đã bị sửa ở tab khác → từ chối
        if ($request->updated_at !== $order->updated_at->toIso8601String()) {
            return response()->json(['message' => __('messages.order_conflict')], 409);
        }
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Đơn hàng không ở trạng thái chờ xử lý.'], 422);
        }
        $order->update(['status' => 'shipping']); // Chuyển sang đang giao hàng
        return response()->json(['success' => true, 'message' => __('messages.update_success'), 'order' => new OrderResource($order)]);
    }

    /**
     * POST /admin/orders/{id}/complete – Hoàn thành đơn: shipping → completed (Optimistic Locking).
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

    // ===================== [Phan Đình Hạnh - 4.1.9] HỦY ĐƠN HÀNG =====================

    /**
     * POST /orders/{id}/cancel – Hủy đơn và hoàn tồn kho tự động.
     * $request->updated_at: timestamp Optimistic Locking. Trả về: 409 nếu xung đột.
     */
    public function cancel(Request $request, Order $order)
    {
        $request->validate(['updated_at' => 'required|string']);
        try {
            // OrderService sẽ kiểm tra quyền, trạng thái, Optimistic Locking và hoàn kho trong Transaction
            $updatedOrder = $this->orderService->cancelOrder($order, $request->user(), $request->updated_at);
            return response()->json(['success' => true, 'message' => __('messages.update_success'), 'order' => new OrderResource($updatedOrder)]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // ===================== [Phan Đình Hạnh - 4.1.10] XÓA VĨNH VIỄN ĐƠN HÀNG =====================

    /**
     * DELETE /admin/orders/{id} – Xóa vĩnh viễn đơn hàng.
     * Ràng buộc: chỉ được xóa đơn có status = 'cancelled' (đã hủy).
     * Cascade: tự động xóa order_items liên quan (đã cấu hình cascadeOnDelete trong migration).
     */
    public function destroy(Order $order)
    {
        // Chỉ xóa được đơn đã hủy → tránh xóa nhầm đơn đang xử lý
        if ($order->status !== 'cancelled') {
            return response()->json(['message' => 'Chỉ có thể xóa vĩnh viễn các đơn hàng đã bị hủy.'], 422);
        }
        $order->delete(); // Cascade → tự xóa order_items
        return response()->json(['success' => true, 'message' => __('messages.order_deleted')]);
    }
}
