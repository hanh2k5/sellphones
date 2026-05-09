<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voucher;
use Carbon\Carbon;

class VoucherSeeder extends Seeder
{
    public function run()
    {
        Voucher::create([
            'code'           => 'GIAM20',
            'discount_type'  => 'percent',
            'discount_value' => 20,
            'min_order_value'=> 500000,
            'max_discount'   => 200000,
            'expires_at'     => Carbon::now()->addDays(30),
            'usage_limit'    => 100,
            'used_count'     => 0,
        ]);

        Voucher::create([
            'code'           => 'HE100',
            'discount_type'  => 'fixed',
            'discount_value' => 100000,
            'min_order_value'=> 1000000,
            'max_discount'   => 100000,
            'expires_at'     => Carbon::now()->addDays(30),
            'usage_limit'    => 50,
            'used_count'     => 0,
        ]);
    }
}
