<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherSeeder extends Seeder
{
    public function run()
    {
        // 1. Voucher giảm theo % (Giảm 10% tối đa 500k cho đơn từ 2 triệu)
        Voucher::create([
            'code'           => 'SELLPHONES10',
            'discount_type'  => 'percent',
            'discount_value' => 10,
            'min_order_value'=> 2000000,
            'max_discount'   => 500000,
            'expires_at'     => Carbon::now()->addDays(30),
            'usage_limit'    => 100,
            'used_count'     => 0,
        ]);

        // 2. Voucher giảm tiền mặt lớn (Giảm thẳng 1 triệu cho đơn mua điện thoại từ 15 triệu)
        Voucher::create([
            'code'           => 'VIPPHONE1M',
            'discount_type'  => 'fixed',
            'discount_value' => 1000000,
            'min_order_value'=> 15000000,
            'max_discount'   => 1000000,
            'expires_at'     => Carbon::now()->addDays(30),
            'usage_limit'    => 50,
            'used_count'     => 0,
        ]);

        // 3. Voucher cho học sinh sinh viên (Giảm 200k cho đơn từ 5 triệu)
        Voucher::create([
            'code'           => 'HSSV200K',
            'discount_type'  => 'fixed',
            'discount_value' => 200000,
            'min_order_value'=> 5000000,
            'max_discount'   => 200000,
            'expires_at'     => Carbon::now()->addDays(30),
            'usage_limit'    => 200,
            'used_count'     => 0,
        ]);

        // 4. MÃ TEST: Hết hạn sử dụng (để test thông báo lỗi)
        Voucher::create([
            'code'           => 'EXPIRED2025',
            'discount_type'  => 'fixed',
            'discount_value' => 50000,
            'min_order_value'=> 100000,
            'max_discount'   => 50000,
            'expires_at'     => Carbon::now()->subDays(1), // Hết hạn hôm qua
            'usage_limit'    => 100,
            'used_count'     => 0,
        ]);

        // 5. MÃ TEST: Hết lượt sử dụng (để test thông báo lỗi)
        Voucher::create([
            'code'           => 'OUTOFORDER',
            'discount_type'  => 'fixed',
            'discount_value' => 50000,
            'min_order_value'=> 100000,
            'max_discount'   => 50000,
            'expires_at'     => Carbon::now()->addDays(30),
            'usage_limit'    => 10,
            'used_count'     => 10, // Số lần dùng = Giới hạn lượt dùng
        ]);
    }
}
