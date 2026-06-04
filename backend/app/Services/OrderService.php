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

        // Toàn bộ logic bên dưới chạy trong 1 Transaction → rollback nếu có lỗi
        return DB::transaction(function () use ($data, $user, $cartItems) {
            $totalAmount = 0; // Tổng tiền hàng (chưa giảm giá)

            foreach ($cartItems as $item) {
                // Pessimistic Locking: khóa dòng SP trong DB để tránh 2 người cùng đặt cuốc hàng cuối
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product || $product->stock < $item->quantity) {
                    throw new Exception(__('messages.stock_insufficient', ['name' => $item->product->name]), 422);
                }

                if ($item->quantity <= 0) {
                    throw new Exception('Số lượng sản phẩm không hợp lệ.', 422);
                }

                if (!$product->is_active) {
                    throw new Exception("Sản phẩm {$product->name} đã ngừng kinh doanh.", 422);
                }

                $product->decrement('stock', $item->quantity); // Trừ tồn kho
                $totalAmount += $product->price * $item->quantity; // Cộng dồn tổng tiền
            }

            // ===================== [Phan Đình Hạnh - 4.1.13] ÁP DỤNG VOUCHER =====================
            $discount  = 0;    // Số tiền được giảm (tính ra từ voucher)
            $voucherId = null;  // ID voucher sẽ gắn vào đơn hàng (null nếu không dùng)

            if (!empty($data['voucher_code'])) {
                // Lock voucher để tránh 2 người dùng cùng nhập 1 mã cùng lúc vượt giới hạn
                $voucher = Voucher::where('code', $data['voucher_code'])->lockForUpdate()->first();

                if ($voucher && $voucher->isValid()) {
                    // Kiểm tra user này đã từng dùng voucher này chưa (chống lạm dụng)
                    $alreadyUsed = Order::where('user_id', $user->id)
                        ->where('voucher_id', $voucher->id)
                        ->where('status', '!=', 'cancelled')
                        ->exists();

                    if ($alreadyUsed) {
                        throw new Exception('Bạn đã sử dụng mã giảm giá này cho một đơn hàng khác.', 422);
                    }

                    $discount  = $voucher->calculateDiscount($totalAmount); // Tính số tiền giảm
                    $voucherId = $voucher->id;
                    $voucher->increment('used_count'); // Tăng lượt sử dụng voucher
                }
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
                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $item->product_id,
                    'quantity'          => $item->quantity,
                    'price_at_purchase' => $item->product->price, // Lưu giá tại thời điểm đặt
                ]);
            }

            CartItem::where('user_id', $user->id)->delete(); // Xóa toàn bộ giỏ hàng sau khi đặt

            return $order->load(['items.product', 'voucher']); // Trả về đơn hàng kèm chi tiết
        });
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

        // Không được hủy đơn đã hoàn thành hoặc đã hủy trước đó
        if (in_array($order->status, ['completed', 'cancelled'])) {
            throw new Exception('Không thể hủy đơn hàng đã hoàn thành hoặc đã bị hủy trước đó.', 422);
        }

        // Optimistic Locking: nếu đơn đã bị sửa bởi tab/người khác → từ chối 409
        if ($clientUpdatedAt && $order->updated_at->toIso8601String() !== $clientUpdatedAt) {
            throw new Exception(__('messages.order_conflict'), 409);
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
}
