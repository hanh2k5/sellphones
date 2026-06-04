<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Http\Requests\ConfirmOrderRequest;
use App\Http\Requests\CompleteOrderRequest;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Exception;

/**
 * [Phan Đình Hạnh - 4.1.4, 4.1.7, 4.1.8, 4.1.9] OrderController
 * Thin Controller: không có Order::where/find, không có Optimistic Locking inline,
 * không có business logic. Toàn bộ đã được di chuyển vào OrderService.
 */
class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    // ===================== [4.1.7] DANH SÁCH ĐƠN HÀNG =====================

    /**
     * GET /orders hoặc /admin/orders — dùng chung, phân quyền qua isAdmin flag.
     * OrderService@getOrders xử lý query, filter, eager loading, phân trang.
     */
    public function index(Request $request)
    {
        $user    = $request->user();
        $isAdmin = $request->is('api/admin/*') && $user->isAdmin();
        $orders  = $this->orderService->getOrders($request->all(), $user, $isAdmin);
        return OrderResource::collection($orders);
    }

    // ===================== [4.1.4] ĐẶT HÀNG =====================

    public function store(StoreOrderRequest $request)
    {
        try {
            $order = $this->orderService->createOrder($request->validated(), $request->user());
            return response()->json([
                'order'   => (new OrderResource($order))->resolve(),
                'message' => __('messages.order_success')
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // ===================== [4.1.8] CHI TIẾT ĐƠN HÀNG =====================

    /**
     * GET /orders/{id} — OrderService@getOrderDetail kiểm tra quyền và eager-load.
     */
    public function show(Request $request, Order $order)
    {
        try {
            $order = $this->orderService->getOrderDetail($order, $request->user());
            return (new OrderResource($order))->resolve();
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }

    // ===================== [4.1.14] XÁC NHẬN THANH TOÁN MOMO =====================

    public function confirmPayment(Request $request, Order $order)
    {
        try {
            $this->orderService->confirmPayment($order, $request->user()->id);
            return response()->json(['success' => true, 'message' => __('messages.momo_success')]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    // ===================== [4.1.8] DUYỆT ĐƠN (OPTIMISTIC LOCKING) =====================

    /**
     * PUT /admin/orders/{id}/status — Optimistic Locking nằm trong OrderService@updateStatus.
     * Response 409 vẫn trả về current_data để Frontend cập nhật UI.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => __('messages.unauthorized')], 403);
        }

        try {
            $updatedOrder = $this->orderService->updateStatus($order, $request->status, $request->last_updated_at);
            return response()->json([
                'success' => true,
                'message' => __('messages.update_success'),
                'order'   => new OrderResource($updatedOrder),
            ]);
        } catch (Exception $e) {
            if ($e->getCode() === 409) {
                return response()->json([
                    'success'      => false,
                    'message'      => $e->getMessage(),
                    'current_data' => new OrderResource($order), // Trả data mới nhất để client cập nhật
                ], 409);
            }
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // ===================== [4.1.6 & 4.1.8] ADMIN QUẢN LÝ ĐƠN =====================

    /**
     * GET /admin/orders — bộ lọc status/search riêng cho trang quản lý admin.
     * OrderService@getAdminOrders xử lý toàn bộ query/filter.
     */
    public function adminIndex(Request $request)
    {
        $orders = $this->orderService->getAdminOrders($request->all());
        return OrderResource::collection($orders);
    }

    /**
     * POST /admin/orders/{id}/confirm — Optimistic Locking + status check trong OrderService@confirmOrder.
     */
    public function confirmOrder(ConfirmOrderRequest $request, Order $order)
    {
        try {
            $updatedOrder = $this->orderService->confirmOrder($order, $request->status, $request->updated_at);
            return response()->json([
                'success' => true,
                'message' => __('messages.update_success'),
                'order'   => new OrderResource($updatedOrder),
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    /**
     * POST /admin/orders/{id}/complete — Optimistic Locking + status check trong OrderService@completeOrder.
     */
    public function completeOrder(CompleteOrderRequest $request, Order $order)
    {
        try {
            $updatedOrder = $this->orderService->completeOrder($order, $request->updated_at);
            return response()->json([
                'success' => true,
                'message' => __('messages.update_success'),
                'order'   => new OrderResource($updatedOrder),
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    // ===================== [4.1.9] HỦY ĐƠN HÀNG =====================

    /**
     * POST /orders/{id}/cancel — Route Model Binding, OrderService@cancelOrder xử lý toàn bộ.
     */
    public function cancel(CompleteOrderRequest $request, Order $order)
    {
        try {
            $updatedOrder = $this->orderService->cancelOrder($order, $request->user(), $request->updated_at);
            return response()->json([
                'success' => true,
                'message' => __('messages.update_success'),
                'order'   => new OrderResource($updatedOrder),
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    // ===================== [4.1.10] XÓA VĨNH VIỄN ĐƠN HÀNG =====================

    public function destroy(Order $order)
    {
        try {
            $this->orderService->forceDeleteOrder($order);
            return response()->json(['success' => true, 'message' => 'Đã xóa vĩnh viễn đơn hàng khỏi hệ thống.']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}
