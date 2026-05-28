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
            'code'           => 'Giam3trieu',
            'discount_type'  => 'percent',
            'discount_value' => 30,
            'min_order_value'=> 1000000,
            'max_discount'   => 3000000,
            'expires_at'     => Carbon::now()->addDays(30),
            'usage_limit'    => 100,
            'used_count'     => 0,
        ]);

        Voucher::create([
            'code'           => 'Giam5trieu',
            'discount_type'  => 'fixed',
            'discount_value' => 5000000,
            'min_order_value'=> 1000000,
            'max_discount'   => 5000000,
            'expires_at'     => Carbon::now()->addDays(30),
            'usage_limit'    => 100,
            'used_count'     => 0,
        ]);
    }
}
