<?php

namespace Database\Factories;

use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        return [
            'code'            => strtoupper(fake()->lexify('????????')),
            'discount_type'   => fake()->randomElement(['fixed', 'percent']),
            'discount_value'  => fake()->numberBetween(10000, 100000),
            'min_order_value' => 0,
            'usage_limit'     => 100,
            'used_count'      => 0,
            'expires_at'      => now()->addDays(30),
        ];
    }
}
