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
            throw new Exception(__('messages.voucher_not_found'), 404);
        }

        if (!$voucher->isValid()) {
            throw new Exception(__('messages.voucher_expired'), 422);
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
}
