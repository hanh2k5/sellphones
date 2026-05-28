<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Str;

class SpamOrderSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'hanh2005k@gmail.com')->first();
        if (!$user) {
            $this->command->error("User Hạnh not found!");
            return;
        }

        $products = Product::where('is_active', true)->limit(5)->get();
        if ($products->isEmpty()) {
            $this->command->error("No products found!");
            return;
        }

        $this->command->info("Tạo 50 đơn hàng chờ duyệt cho tài khoản Hạnh...");

        for ($i = 0; $i < 50; $i++) {
            $product = $products->random();
            $quantity = rand(1, 3);
            $total = $product->price * $quantity;

            $order = Order::create([
                'user_id' => $user->id,
                'order_code' => 'ORD-' . strtoupper(Str::random(10)),
                'receiver_name' => $user->name,
                'phone' => $user->phone ?? '0123456789',
                'shipping_address' => $user->address ?? '250 Nguyễn Văn Cừ, Quận 5, TP.HCM',
                'total_amount' => $total,
                'status' => 'pending', // Chờ duyệt
                'payment_status' => 'pending',
                'payment_method' => rand(0, 1) ? 'cod' : 'momo',
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price_at_purchase' => $product->price,
            ]);
        }

        $this->command->info("Đã tạo thành công 50 đơn hàng chờ duyệt!");
    }
}
