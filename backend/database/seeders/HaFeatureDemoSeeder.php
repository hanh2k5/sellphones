<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class HaFeatureDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->createDemoImageFiles();

        $category = Category::firstOrCreate(
            ['slug' => 'ha-demo'],
            [
                'name' => 'HA Demo',
                'parent_id' => null,
                'is_active' => true,
            ]
        );

        $childCategory = Category::firstOrCreate(
            ['slug' => 'ha-demo-dien-thoai'],
            [
                'name' => 'HA Demo - Dien thoai',
                'parent_id' => $category->id,
                'is_active' => true,
            ]
        );

        $product = Product::updateOrCreate(
            ['name' => 'HA Demo - San pham co nhieu anh'],
            [
                'category_id' => $childCategory->id,
                'price' => 12345000,
                'stock' => 25,
                'is_active' => true,
                'is_featured' => true,
                'hinh_anh' => 'demo/ha-phone-main.svg',
                'description' => 'San pham demo cho Ha chup man hinh: tim kiem, loc gia, chi tiet, gallery anh va danh gia.',
            ]
        );

        foreach (['demo/ha-gallery-1.svg', 'demo/ha-gallery-2.svg', 'demo/ha-gallery-3.svg'] as $path) {
            ProductImage::firstOrCreate([
                'product_id' => $product->id,
                'image_path' => $path,
            ]);
        }

        $trashProduct = Product::withTrashed()->updateOrCreate(
            ['name' => 'HA Demo - San pham trong thung rac'],
            [
                'category_id' => $childCategory->id,
                'price' => 990000,
                'stock' => 3,
                'is_active' => true,
                'is_featured' => false,
                'hinh_anh' => 'demo/ha-trash-product.svg',
                'description' => 'San pham mau dung de chup man hinh thung rac va khoi phuc san pham.',
            ]
        );

        if (!$trashProduct->trashed()) {
            $trashProduct->delete();
        }

        $user = User::firstOrCreate(
            ['email' => 'hanh2005k@gmail.com'],
            [
                'name' => 'Hanh',
                'password' => Hash::make('11111111'),
                'role' => 'user',
                'is_active' => true,
                'address' => '250 Nguyen Van Cu, Quan 5, TP.HCM',
                'phone' => '0987654321',
                'email_verified_at' => now(),
            ]
        );

        $otherUser = User::firstOrCreate(
            ['email' => 'ha@gmail.com'],
            [
                'name' => 'Khach demo Ha',
                'password' => Hash::make('11111111'),
                'role' => 'user',
                'is_active' => true,
                'address' => 'Dia chi demo cho tinh nang danh gia',
                'phone' => '0912345678',
                'email_verified_at' => now(),
            ]
        );

        $completedOrder = $this->createCompletedOrder(
            'HA-DEMO-COMPLETED-01',
            $user,
            $product,
            1
        );

        $secondCompletedOrder = $this->createCompletedOrder(
            'HA-DEMO-COMPLETED-02',
            $otherUser,
            $product,
            1
        );

        Review::updateOrCreate(
            [
                'order_id' => $completedOrder->id,
                'product_id' => $product->id,
            ],
            [
                'user_id' => $user->id,
                'rating' => 5,
                'comment' => 'San pham demo cua Ha hien thi dung gallery, loc gia va danh gia.',
                'status' => 'approved',
                'created_at' => now()->subDays(2),
            ]
        );

        Review::updateOrCreate(
            [
                'order_id' => $secondCompletedOrder->id,
                'product_id' => $product->id,
            ],
            [
                'user_id' => $otherUser->id,
                'rating' => 4,
                'comment' => 'Review mau de admin xem danh sach va loc theo so sao.',
                'status' => 'approved',
                'created_at' => now()->subDay(),
            ]
        );

        $product->recalcAvgRating();
    }

    private function createCompletedOrder(string $code, User $user, Product $product, int $quantity): Order
    {
        $order = Order::updateOrCreate(
            ['order_code' => $code],
            [
                'user_id' => $user->id,
                'receiver_name' => $user->name,
                'phone' => $user->phone ?? '0987654321',
                'total_amount' => $product->price * $quantity,
                'discount_amount' => 0,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => 'cod',
                'shipping_address' => $user->address ?? 'Dia chi demo',
            ]
        );

        OrderItem::updateOrCreate(
            [
                'order_id' => $order->id,
                'product_id' => $product->id,
            ],
            [
                'quantity' => $quantity,
                'price_at_purchase' => $product->price,
            ]
        );

        return $order;
    }

    private function createDemoImageFiles(): void
    {
        $images = [
            'demo/ha-phone-main.svg' => ['#2563eb', 'HA DEMO'],
            'demo/ha-gallery-1.svg' => ['#16a34a', 'GALLERY 1'],
            'demo/ha-gallery-2.svg' => ['#f59e0b', 'GALLERY 2'],
            'demo/ha-gallery-3.svg' => ['#dc2626', 'GALLERY 3'],
            'demo/ha-trash-product.svg' => ['#64748b', 'TRASH'],
        ];

        foreach ($images as $path => [$color, $label]) {
            Storage::disk('public')->put($path, $this->svg($color, $label));
        }
    }

    private function svg(string $color, string $label): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 900 900">
  <rect width="900" height="900" fill="#f8fafc"/>
  <rect x="190" y="90" width="520" height="720" rx="64" fill="$color"/>
  <rect x="235" y="150" width="430" height="590" rx="36" fill="#ffffff" opacity="0.92"/>
  <circle cx="450" cy="760" r="24" fill="#e2e8f0"/>
  <text x="450" y="430" text-anchor="middle" font-family="Arial, sans-serif" font-size="64" font-weight="700" fill="#0f172a">$label</text>
  <text x="450" y="500" text-anchor="middle" font-family="Arial, sans-serif" font-size="34" fill="#334155">Sellphones</text>
</svg>
SVG;
    }
}
