<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Voucher extends Model
{
    protected $fillable = [
        'code', 'discount_type', 'discount_value', 'min_order_value', 
        'max_discount', 'start_date', 'end_date', 'usage_limit', 'used_count', 'is_active'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date'   => 'datetime',
        'is_active'  => 'boolean',
    ];

    public function isValid()
    {
        $now = Carbon::now();
        return $this->is_active 
            && $this->start_date <= $now 
            && $this->end_date >= $now 
            && $this->used_count < $this->usage_limit;
    }

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
