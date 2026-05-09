<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Voucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'min_order_value', 
        'max_discount', 'usage_limit', 'used_count', 'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Kiểm tra Voucher có hợp lệ để sử dụng hay không.
     * Điều kiện: Trong thời hạn và còn lượt dùng.
     * 
     * @return bool
     */
    public function isValid()
    {
        $now = Carbon::now();
        
        // Kiểm tra thời hạn
        if ($this->expires_at && $this->expires_at < $now) return false;

        // Kiểm tra lượt dùng (Nếu usage_limit > 0 thì mới check giới hạn)
        if ($this->usage_limit > 0 && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    /**
     * Tính toán số tiền được giảm dựa trên tổng giá trị đơn hàng.
     * Hỗ trợ giảm theo % (có giới hạn tối đa) hoặc giảm số tiền cố định.
     * 
     * @param float $totalAmount Tổng tiền tạm tính của đơn hàng
     * @return float Số tiền được giảm
     */
    public function calculateDiscount($totalAmount)
    {
        if ($totalAmount < $this->min_order_value) return 0;

        $discount = 0;
        if ($this->discount_type === 'percent') {
            $discount = ($totalAmount * $this->discount_value) / 100;
            if ($this->max_discount > 0) {
                $discount = min($discount, $this->max_discount);
            }
        } else {
            $discount = $this->discount_value;
        }

        return min($discount, $totalAmount);
    }
}
