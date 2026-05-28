<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'order_code' => 'ORD-' . strtoupper(uniqid()),
            'receiver_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'total_amount' => 100000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
            'shipping_address' => fake()->address(),
        ];
    }
}
