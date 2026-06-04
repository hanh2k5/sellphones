<?php

namespace App\Services;

use App\Models\Voucher;
use Exception;

class VoucherService
{
    /**
     * Kiểm tra tính hợp lệ của mã Voucher và trả về Model nếu hợp lệ.
     * Ném ra Exception nếu mã không tồn tại hoặc hết hạn.
     * 
     * @param string $code Mã voucher cần kiểm tra
     * @return Voucher
     * @throws Exception
     */
    public function validateCode($code)
    {
        $voucher = Voucher::where('code', strtoupper($code))->first();

        if (!$voucher) {
            throw new Exception('Mã giảm giá không hợp lệ.', 404);
        }

        if (!$voucher->isValid()) {
            throw new Exception('Mã giảm giá đã hết hạn hoặc hết lượt sử dụng.', 422);
        }

        return $voucher;
    }

    /**
     * Truy vấn danh sách Voucher đang hoạt động để hiển thị lên Frontend.
     * Lọc theo thời gian hết hạn và lượt sử dụng còn lại.
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveVouchers()
    {
        return Voucher::where('expires_at', '>=', now())
            ->where(function($q) {
                $q->where('usage_limit', 0)
                  ->orWhereRaw('used_count < usage_limit');
            })
            ->get();
    }

    /**
     * Áp dụng mã giảm giá và kiểm tra điều kiện sử dụng của User.
     *
     * @param string $code
     * @param \App\Models\User $user
     * @return array
     * @throws Exception
     */
    public function applyVoucher($code, $user)
    {
        $voucher = $this->validateCode($code);

        if ($user) {
            $alreadyUsed = \App\Models\Order::where('user_id', $user->id)
                ->where('voucher_id', $voucher->id)
                ->where('status', '!=', 'cancelled')
                ->exists();
            if ($alreadyUsed) {
                throw new Exception('Bạn đã sử dụng mã giảm giá này cho một đơn hàng khác.', 422);
            }
        }

        $cartItems   = \App\Models\CartItem::where('user_id', $user->id)->with('product')->get();
        $totalAmount = $cartItems->sum(fn($item) => $item->product->price * $item->quantity);

        if ($totalAmount < $voucher->min_order_value) {
            throw new Exception(
                __('messages.min_order_error', ['min' => number_format($voucher->min_order_value, 0, ',', '.') . 'đ']),
                422
            );
        }

        $discount = $voucher->calculateDiscount($totalAmount);

        return [
            'voucher'  => $voucher,
            'discount' => $discount,
        ];
    }
}
