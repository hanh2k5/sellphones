<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'product_id' => \App\Models\Product::factory(),
            'order_id' => \App\Models\Order::factory(),
            'rating' => 5,
            'comment' => fake()->sentence(),
            'status' => 'approved',
        ];
    }
}
