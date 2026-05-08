<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Service xử lý toàn bộ logic nghiệp vụ liên quan đến Đơn hàng.
 * [Phan Đình Hạnh]
 */
class OrderService
{
    /**
     * [Phan Đình Hạnh - 4.1.5] Tạo đơn hàng mới (Order Creation & Transaction)
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
                // Pessimistic Locking: Chống lỗi bán quá số lượng (Overselling)
                $product = Product::lockForUpdate()->find($item->product_id);

                if (!$product || $product->stock < $item->quantity) {
                    throw new Exception("Sản phẩm {$item->product->name} không đủ tồn kho.", 422);
                }
                
                $product->decrement('stock', $item->quantity);
                $totalAmount += $product->price * $item->quantity;
            }

            $discount = 0;
            $voucherId = null;
            if (!empty($data['voucher_code'])) {
                $voucher = Voucher::where('code', $data['voucher_code'])->first();
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

    /**
     * [Phan Đình Hạnh - 4.1.9] Hủy đơn hàng và Hoàn tồn kho tự động
     */
    public function cancelOrder(Order $order, $user, $clientUpdatedAt = null)
    {
        if (!$user->isAdmin() && $order->user_id !== $user->id) {
            throw new Exception('Không có quyền hủy đơn hàng.', 403);
        }

        if ($order->status === 'shipping' || $order->status === 'completed') {
            throw new Exception('Không thể hủy đơn hàng đã được duyệt hoặc đã hoàn thành.', 409);
        }

        if ($order->status === 'cancelled') {
            throw new Exception('Đơn hàng đã bị hủy trước đó.', 422);
        }

        return DB::transaction(function () use ($order) {
            $order->load(['items.product' => fn($q) => $q->withTrashed()]);
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            $order->update(['status' => 'cancelled']);
            $this->cacheStatus($order, 'cancelled', '❌ Đơn hàng đã bị hủy.');
            
            return $order->fresh();
        });
    }

    /**
     * [Phan Đình Hạnh - 4.1.8] Duyệt đơn hàng
     */
    public function approveOrder(Order $order, $clientUpdatedAt = null)
    {
        if ($order->status !== 'pending') {
            throw new Exception('Đơn hàng không ở trạng thái chờ duyệt.', 422);
        }

        $order->update(['status' => 'shipping']);
        $this->cacheStatus($order, 'shipping', '🚀 Đơn hàng đã được duyệt.');

        return $order->fresh();
    }

    public function completeOrder(Order $order, $clientUpdatedAt = null)
    {
        if ($order->status !== 'shipping') {
            throw new Exception('Chỉ có thể hoàn tất đơn hàng đang giao.', 422);
        }

        $order->update(['status' => 'completed', 'payment_status' => 'paid']);
        $this->cacheStatus($order, 'completed', '🎉 Đơn hàng đã hoàn tất.');

        return $order->fresh();
    }

    private function cacheStatus(Order $order, $status, $message)
    {
        cache()->put("order_status_{$order->id}", [
            'status'     => $status,
            'updated_at' => now()->toIso8601String(),
            'message'    => $message,
        ], 120);
    }
}
