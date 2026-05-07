<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoucherSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('vouchers')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('vouchers')->insert([
            [
                'code' => 'SALE500K',
                'discount_type' => 'fixed',
                'discount_value' => 500000,
                'min_order_value' => 5000000,
                'max_discount' => null,
                'usage_limit' => 100,
                'used_count' => 0,
                'expires_at' => now()->addYear(),
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'GIAM20',
                'discount_type' => 'percent',
                'discount_value' => 20,
                'min_order_value' => 10000000,
                'max_discount' => 2000000,
                'usage_limit' => 50,
                'used_count' => 0,
                'expires_at' => now()->addMonths(6),
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'code' => 'NEWUSER',
                'discount_type' => 'fixed',
                'discount_value' => 1000000,
                'min_order_value' => 0,
                'max_discount' => null,
                'usage_limit' => 200,
                'used_count' => 0,
                'expires_at' => now()->addYear(),
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
    }
}
