<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * [Phan Đình Hạnh - 4.1.4 & 4.1.9 & 4.1.13] OrderService
 * Tầng nghiệp vụ cho Đơn hàng & Voucher.
 * Đảm bảo nhất quán dữ liệu bằng DB Transaction + Pessimistic/Optimistic Locking.
 */
class OrderService
{
    // ===================== [Phan Đình Hạnh - 4.1.4] ĐẶT HÀNG (CHECKOUT) =====================

    /**
     * Thực hiện toàn bộ quy trình đặt hàng trong 1 Transaction:
     *   1. Lấy giỏ hàng của user (bỏ qua SP đã xóa mềm).
     *   2. Lock từng SP (Pessimistic Locking) → trừ tồn kho → tránh bán quá số lượng.
     *   3. Kiểm tra & áp dụng Voucher (nếu có).
     *   4. Tạo bản ghi Order + OrderItems.
     *   5. Xóa giỏ hàng.
     *
     * $data: thông tin người nhận & thanh toán (receiver_name, phone, shipping_address, ...).
     * $user: người đang đặt hàng.
     * Trả về: Order vừa tạo kèm items và voucher.
     * Throws: Exception 422 nếu giỏ rỗng / hết hàng / SP ngừng kinh doanh.
     */
    public function createOrder(array $data, $user)
    {
        // Lấy giỏ hàng của user, bỏ qua các SP đã bị soft-delete
        $cartItems = CartItem::where('user_id', $user->id)
            ->whereHas('product', fn($q) => $q->whereNull('deleted_at'))
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            throw new Exception(__('messages.cart_empty'), 422); // Giỏ hàng rỗng
        }

        DB::beginTransaction();
        try {
            $totalAmount = 0; // Tổng tiền hàng (chưa giảm giá)
            $lockedProducts = [];

            foreach ($cartItems as $item) {
                // Pessimistic Locking: khóa dòng SP trong DB để tránh 2 người cùng đặt cuốc hàng cuối
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product || $product->stock < $item->quantity) {
                    throw new Exception('Sản phẩm vừa hết hàng.', 422);
                }

                if ($item->quantity <= 0) {
                    throw new Exception('Số lượng sản phẩm không hợp lệ.', 422);
                }

                if (!$product->is_active) {
                    throw new Exception("Sản phẩm {$product->name} đã ngừng kinh doanh.", 422);
                }

                $product->decrement('stock', $item->quantity); // Trừ tồn kho
                $totalAmount += $product->price * $item->quantity; // Cộng dồn tổng tiền
                
                $lockedProducts[$item->product_id] = $product;
            }

            // ===================== [Phan Đình Hạnh - 4.1.13] ÁP DỤNG VOUCHER =====================
            $discount  = 0;    // Số tiền được giảm (tính ra từ voucher)
            $voucherId = null;  // ID voucher sẽ gắn vào đơn hàng (null nếu không dùng)

            if (!empty($data['voucher_code'])) {
                // Lock voucher để tránh 2 người dùng cùng nhập 1 mã cùng lúc vượt giới hạn
                $voucher = Voucher::where('code', $data['voucher_code'])->lockForUpdate()->first();

                if (!$voucher) {
                    throw new Exception('Mã giảm giá không hợp lệ.', 404);
                }

                if (!$voucher->isValid()) {
                    throw new Exception('Mã giảm giá đã hết hạn hoặc hết lượt sử dụng.', 422);
                }

                // Kiểm tra user này đã từng dùng voucher này chưa (chống lạm dụng)
                $alreadyUsed = Order::where('user_id', $user->id)
                    ->where('voucher_id', $voucher->id)
                    ->where('status', '!=', 'cancelled')
                    ->exists();

                if ($alreadyUsed) {
                    throw new Exception('Bạn đã sử dụng mã giảm giá này cho một đơn hàng khác.', 422);
                }

                // Kiểm tra giá trị đơn hàng tối thiểu
                if ($totalAmount < $voucher->min_order_value) {
                    throw new Exception('Đơn hàng không đạt giá trị tối thiểu để áp dụng mã giảm giá.', 422);
                }

                $discount  = $voucher->calculateDiscount($totalAmount); // Tính số tiền giảm
                $voucherId = $voucher->id;
                $voucher->increment('used_count'); // Tăng lượt sử dụng voucher
            }

            // Tạo bản ghi Order (đơn hàng) trong DB
            $order = Order::create([
                'user_id'          => $user->id,
                'order_code'       => 'ORD-' . strtoupper(uniqid()), // Mã đơn hàng duy nhất
                'receiver_name'    => $data['receiver_name'],
                'phone'            => $data['phone'],
                'voucher_id'       => $voucherId,
                'total_amount'     => max(0, $totalAmount - $discount), // Không âm
                'discount_amount'  => $discount,
                'status'           => 'pending',   // Trạng thái ban đầu: chờ xử lý
                'payment_status'   => 'pending',   // Trạng thái thanh toán: chờ
                'payment_method'   => $data['payment_method'],
                'shipping_address' => $data['shipping_address'],
            ]);

            // Tạo từng dòng OrderItem (chi tiết sản phẩm trong đơn hàng)
            foreach ($cartItems as $item) {
                $productPrice = isset($lockedProducts[$item->product_id]) ? $lockedProducts[$item->product_id]->price : $item->product->price;
                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $item->product_id,
                    'quantity'          => $item->quantity,
                    'price_at_purchase' => $productPrice, // Lưu giá tại thời điểm đặt (lấy từ SP đã được lock)
                ]);
            }

            CartItem::where('user_id', $user->id)->delete(); // Xóa toàn bộ giỏ hàng sau khi đặt

            DB::commit();

            return $order->load(['items.product', 'voucher']); // Trả về đơn hàng kèm chi tiết
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    // ===================== [Phan Đình Hạnh - 4.1.9] HỦY ĐƠN HÀNG & HOÀN KHO =====================

    /**
     * Hủy đơn hàng và tự động hoàn lại tồn kho.
     * Dùng Optimistic Locking + DB Transaction để đảm bảo nhất quán.
     *
     * $order: đơn cần hủy | $user: người thực hiện hủy | $clientUpdatedAt: timestamp client giữ.
     * Trả về: đơn hàng sau khi hủy (status = 'cancelled').
     * Throws: Exception 403 (không có quyền) / 422 (trạng thái không hợp lệ) / 409 (xung đột).
     */
    public function cancelOrder(Order $order, $user, $clientUpdatedAt = null)
    {
        // Chỉ Admin hoặc chủ đơn mới được hủy
        if (!$user->isAdmin() && $order->user_id !== $user->id) {
            throw new Exception(__('messages.unauthorized'), 403);
        }

        // Optimistic Locking: nếu đơn đã bị sửa bởi tab/người khác → từ chối 409
        if ($clientUpdatedAt) {
            try {
                $clientTime = \Carbon\Carbon::parse($clientUpdatedAt);
                if ($order->updated_at->timestamp !== $clientTime->timestamp) {
                    throw new Exception('Lỗi: Đơn hàng đã được xử lý bởi người khác!', 409);
                }
            } catch (\Exception $e) {
                if ($e->getCode() === 409) {
                    throw $e;
                }
                throw new Exception('Lỗi: Đơn hàng đã được xử lý bởi người khác!', 409);
            }
        }

        // Ràng buộc: chỉ được phép hủy nếu trạng thái đơn là pending hoặc confirmed
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            throw new Exception('Không thể hủy đơn hàng ở trạng thái hiện tại.', 422);
        }

        return DB::transaction(function () use ($order) {
            // Hoàn tồn kho: cộng lại số lượng từng sản phẩm trong đơn
            $order->load(['items.product' => fn($q) => $q->withTrashed()]); // withTrashed để lấy cả SP đã xóa mềm
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity); // Cộng lại kho
                }
            }

            $order->update(['status' => 'cancelled']); // Cập nhật trạng thái đơn = đã hủy
            return $order->fresh(); // Trả về bản ghi đơn hàng mới nhất từ DB
        });
    }

    // ===================== METHODS DI CHUYỂN TỪ CONTROLLER =====================

    /**
     * Lấy danh sách đơn hàng: Admin xem tất cả + thông tin user, User chỉ xem đơn của mình.
     */
    public function getOrders(array $filters, $user, bool $isAdmin): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Order::with(['items.product' => fn($q) => $q->withTrashed(), 'voucher'])
            ->filter($filters);

        if ($isAdmin) {
            $query->with('user:id,name,email');
        } else {
            $query->where('user_id', $user->id);
        }

        $perPage = $filters['per_page'] ?? 10;
        return $query->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate($perPage);
    }

    /**
     * Danh sách đơn hàng cho Admin với bộ lọc status/search riêng (dùng cho adminIndex route).
     */
    public function getAdminOrders(array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Order::with(['items.product' => fn($q) => $q->withTrashed(), 'voucher', 'user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%$search%")
                  ->orWhere('receiver_name', 'like', "%$search%")
                  ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%$search%")
                                                     ->orWhere('email', 'like', "%$search%"));
            });
        }

        $perPage = $filters['per_page'] ?? 10;
        return $query->paginate($perPage);
    }

    /**
     * Lấy chi tiết đơn hàng. Kiểm tra quyền: Admin hoặc chủ đơn.
     * Throws Exception 403 nếu không có quyền.
     */
    public function getOrderDetail(Order $order, $user): Order
    {
        if (!$user->isAdmin() && $order->user_id !== $user->id) {
            throw new Exception(__('messages.unauthorized'), 403);
        }
        $order->load(['items.product' => fn($q) => $q->withTrashed(), 'voucher', 'user:id,name,email']);
        return $order;
    }

    /**
     * Xác nhận thanh toán MoMo (Idempotency Guard).
     * Throws Exception 403/422 nếu không hợp lệ.
     */
    public function confirmPayment(Order $order, int $userId): void
    {
        if ($order->user_id !== $userId) {
            throw new Exception(__('messages.unauthorized'), 403);
        }
        if ($order->status === 'cancelled') {
            throw new Exception('Cancelled', 422);
        }
        if ($order->payment_status === 'paid') {
            throw new Exception(__('messages.already_paid'), 422);
        }
        $order->update(['payment_status' => 'paid', 'status' => 'pending']);
    }

    /**
     * Cập nhật trạng thái đơn (Admin). Áp dụng Optimistic Locking.
     * Throws Exception 409 nếu xung đột.
     */
    public function updateStatus(Order $order, string $status, string $clientUpdatedAt): Order
    {
        $this->checkOptimisticLock($order, $clientUpdatedAt);
        $order->update(['status' => $status]);
        return $order->fresh();
    }

    /**
     * Duyệt đơn: pending → confirmed. Áp dụng Optimistic Locking + kiểm tra trạng thái.
     * Throws Exception 409 (xung đột) hoặc 422 (sai trạng thái).
     */
    public function confirmOrder(Order $order, string $status, string $clientUpdatedAt): Order
    {
        $this->checkOptimisticLock($order, $clientUpdatedAt);
        if ($order->status !== 'pending') {
            throw new Exception('Đơn hàng không ở trạng thái chờ xử lý.', 422);
        }
        $order->update(['status' => $status]);
        return $order->fresh();
    }

    /**
     * Hoàn thành đơn: shipping → completed. Áp dụng Optimistic Locking + kiểm tra trạng thái.
     * Throws Exception 409 (xung đột) hoặc 422 (sai trạng thái).
     */
    public function completeOrder(Order $order, string $clientUpdatedAt): Order
    {
        $this->checkOptimisticLock($order, $clientUpdatedAt);
        if ($order->status !== 'shipping') {
            throw new Exception('Đơn hàng không ở trạng thái đang giao.', 422);
        }
        $order->update(['status' => 'completed']);
        return $order->fresh();
    }

    /**
     * Kiểm tra Optimistic Locking: so sánh timestamp client với DB.
     * Throws Exception 409 nếu dữ liệu bị thay đổi bởi tab/người khác.
     * Dùng chung cho updateStatus, confirmOrder, completeOrder (DRY).
     */
    private function checkOptimisticLock(Order $order, string $clientUpdatedAt): void
    {
        try {
            $clientTime = \Carbon\Carbon::parse($clientUpdatedAt);
            if ($order->updated_at->timestamp !== $clientTime->timestamp) {
                throw new Exception('Cảnh báo: Đơn hàng đã thay đổi bởi người khác. Vui lòng tải lại!', 409);
            }
        } catch (Exception $e) {
            if ($e->getCode() === 409) {
                throw $e; // Re-throw nếu là lỗi xung đột
            }
            // Carbon::parse thất bại → cũng coi là xung đột
            throw new Exception('Cảnh báo: Đơn hàng đã thay đổi bởi người khác. Vui lòng tải lại!', 409);
        }
    }

    /**
     * Xóa vĩnh viễn đơn hàng (Admin). Chỉ cho phép xóa đơn hàng đã hủy.
     * Throws Exception 422 nếu đơn hàng chưa hủy.
     */
    public function forceDeleteOrder(Order $order): void
    {
        if ($order->status !== 'cancelled') {
            throw new Exception('Chỉ có thể xóa vĩnh viễn các đơn hàng đã bị hủy.', 422);
        }

        DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->delete();
        });
    }
}

