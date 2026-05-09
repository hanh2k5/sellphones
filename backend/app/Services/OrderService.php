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
 * Service xử lý logic Đơn hàng & Voucher.
 * Đảm bảo tính nhất quán dữ liệu thông qua Transaction và Pessimistic Locking.
 * [Phan Đình Hạnh - 4.1.4 & 4.1.13]
 */
class OrderService
{
    /**
     * Thực hiện quy trình đặt hàng:
     * 1. Khởi tạo Transaction.
     * 2. Lock sản phẩm & Trừ tồn kho.
     * 3. Kiểm tra & Áp dụng Voucher (nếu có).
     * 4. Tạo bản ghi Order & OrderItems.
     * 5. Xóa giỏ hàng.
     * 
     * @param array $data Thông tin người nhận & phương thức thanh toán
     * @param User $user Người dùng hiện tại
     * @return Order
     * @throws Exception
     */
    public function createOrder(array $data, $user)
    {
        $cartItems = CartItem::where('user_id', $user->id)
            ->whereHas('product', fn($q) => $q->whereNull('deleted_at'))
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            throw new Exception('Giỏ hàng trống.', 422);
        }

        return DB::transaction(function () use ($data, $user, $cartItems) {
            $totalAmount = 0;

            foreach ($cartItems as $item) {
                // Pessimistic Locking: Chống lỗi bán quá số lượng
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product || $product->stock < $item->quantity) {
                    throw new Exception("Sản phẩm {$item->product->name} không đủ tồn kho.", 422);
                }
                
                $product->decrement('stock', $item->quantity);
                $totalAmount += $product->price * $item->quantity;
            }

            // --- XỬ LÝ VOUCHER (Tính năng 4.1.13) ---
            $discount = 0;
            $voucherId = null;
            if (!empty($data['voucher_code'])) {
                // Lock voucher để tránh tranh chấp lượt dùng (Concurrency)
                $voucher = Voucher::where('code', $data['voucher_code'])->lockForUpdate()->first();
                if ($voucher && $voucher->isValid()) {
                    $discount = $voucher->calculateDiscount($totalAmount);
                    $voucherId = $voucher->id;
                    $voucher->increment('used_count');
                }
            }

            $order = Order::create([
                'user_id'          => $user->id,
                'order_code'       => 'ORD-' . strtoupper(uniqid()),
                'receiver_name'    => $data['receiver_name'],
                'phone'            => $data['phone'],
                'voucher_id'       => $voucherId,
                'total_amount'     => max(0, $totalAmount - $discount),
                'discount_amount'  => $discount,
                'status'           => 'pending',
                'payment_status'   => 'pending',
                'payment_method'   => $data['payment_method'],
                'shipping_address' => $data['shipping_address'],
            ]);

            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $item->product_id,
                    'quantity'          => $item->quantity,
                    'price_at_purchase' => $item->product->price,
                ]);
            }

            CartItem::where('user_id', $user->id)->delete();

            return $order->load(['items.product', 'voucher']);
        });
    }
}
