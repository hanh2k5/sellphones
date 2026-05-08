<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('product_images')->truncate();
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $products = [
            // CATERGORY 1: APPLE
            [
                'name' => 'iPhone 15 Pro Max 256GB',
                'price' => 29990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 1,
                'description' => 'Khung viền Titan siêu nhẹ, chip A17 Pro mạnh mẽ nhất. Camera tetraprism zoom quang 5x độc quyền.',
                'stock' => 50, 'is_featured' => true, 'avg_rating' => 5.0,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'iPhone 15 Pro 128GB',
                'price' => 25490000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 1,
                'description' => 'Thiết kế Titan gọn nhẹ, màn hình 6.1 inch 120Hz ProMotion mượt mà. Camera 48MP chụp đêm đỉnh cao.',
                'stock' => 30, 'avg_rating' => 4.8,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'iPhone 15 128GB',
                'price' => 19990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 1,
                'description' => 'Thiết kế bo cong mới, trang bị Dynamic Island và cổng sạc Type-C tiện lợi. Chip A16 Bionic tối ưu pin.',
                'stock' => 100, 'avg_rating' => 4.7,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'iPhone 14 Pro Max 256GB',
                'price' => 26990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 1,
                'description' => 'Siêu phẩm 2022 với màn hình Always-On Display, camera 48MP cực sắc nét và thời lượng pin vô đối.',
                'stock' => 20, 'avg_rating' => 4.9,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'iPhone 13 128GB',
                'price' => 13590000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 1,
                'description' => 'Lựa chọn quốc dân với hiệu năng vẫn rất mạnh mẽ, camera xếp chéo đặc trưng và mức giá cực kỳ dễ tiếp cận.',
                'stock' => 80, 'avg_rating' => 4.6,
                'created_at' => now(), 'updated_at' => now(),
            ],

            // CATERGORY 2: SAMSUNG
            [
                'name' => 'Samsung S24 Ultra 256GB',
                'price' => 28000000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 2,
                'description' => 'Tích hợp Galaxy AI thông minh, khung viền Titan, màn hình phẳng chống chói và camera 200MP zoom 100x.',
                'stock' => 45, 'is_featured' => true, 'avg_rating' => 4.9,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Samsung Galaxy S24 Plus 256GB',
                'price' => 23990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 2,
                'description' => 'Màn hình 2K+ siêu nét, thiết kế nguyên khối sang trọng. Trải nghiệm trọn vẹn các tính năng Galaxy AI tiên tiến.',
                'stock' => 25, 'avg_rating' => 4.7,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Samsung Galaxy Z Fold5 256GB',
                'price' => 31990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 2,
                'description' => 'Điện thoại gập cao cấp nhất, bản lề Flex khít hoàn toàn. Màn hình lớn 7.6 inch nâng tầm đa nhiệm.',
                'stock' => 15, 'avg_rating' => 4.8,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Samsung Galaxy Z Flip5 256GB',
                'price' => 19990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 2,
                'description' => 'Thiết kế gập vỏ sò thời trang, màn hình phụ Flex Window lớn 3.4 inch thao tác không cần mở máy.',
                'stock' => 35, 'avg_rating' => 4.6,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Samsung Galaxy A55 5G',
                'price' => 9990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 2,
                'description' => 'Vua tầm trung với viền kim loại cao cấp, hệ thống camera Nightography chụp đêm cực sáng và pin 5000mAh.',
                'stock' => 120, 'avg_rating' => 4.5,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Samsung Galaxy A35 5G',
                'price' => 7490000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 2,
                'description' => 'Thiết kế kính sang trọng, màn hình Super AMOLED 120Hz rực rỡ và hiệu năng gaming ổn định.',
                'stock' => 150, 'avg_rating' => 4.4,
                'created_at' => now(), 'updated_at' => now(),
            ],

            // CATERGORY 3: OPPO
            [
                'name' => 'Oppo Find N3 512GB',
                'price' => 39990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 3,
                'description' => 'Điện thoại gập tỷ lệ hoàn hảo, hệ thống camera Hasselblad chuyên nghiệp và sạc nhanh SuperVOOC 67W.',
                'stock' => 10, 'is_featured' => true, 'avg_rating' => 4.9,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Oppo Find N3 Flip',
                'price' => 19000000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 3,
                'description' => 'Điện thoại gập dọc duy nhất có camera tele, màn hình ngoài hiển thị dọc tương thích app hoàn hảo.',
                'stock' => 18, 'avg_rating' => 4.7,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Oppo Reno11 Pro 5G',
                'price' => 15990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 3,
                'description' => 'Chuyên gia chân dung với camera góc siêu rộng, mặt lưng thiết kế mặt đá tự nhiên cuốn hút.',
                'stock' => 40, 'avg_rating' => 4.6,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Oppo Reno11 F 5G',
                'price' => 8490000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 3,
                'description' => 'Màn hình viền siêu mỏng, độ sáng cao. Chống nước IP65 và thân máy bền bỉ đáng kinh ngạc.',
                'stock' => 70, 'avg_rating' => 4.5,
                'created_at' => now(), 'updated_at' => now(),
            ],

            // CATERGORY 4: XIAOMI
            [
                'name' => 'Xiaomi 14 Ultra 512GB',
                'price' => 28990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 4,
                'description' => 'Quái vật nhiếp ảnh với cụm camera Leica 4 ống kính 50MP, khẩu độ biến thiên vô cấp chuyên nghiệp.',
                'stock' => 15, 'is_featured' => true, 'avg_rating' => 4.9,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Xiaomi 14 256GB',
                'price' => 19990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 4,
                'description' => 'Cấu hình mạnh mẽ nhất phân khúc với Snapdragon 8 Gen 3 trong một thân hình nhỏ gọn vừa tay cầm.',
                'stock' => 35, 'avg_rating' => 4.8,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Xiaomi Redmi Note 13 Pro+ 5G',
                'price' => 10490000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 4,
                'description' => 'Màn hình cong tràn viền 120Hz, camera 200MP siêu độ phân giải, chống nước IP68 chuẩn flagship.',
                'stock' => 60, 'avg_rating' => 4.6,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Xiaomi Redmi Note 13 Pro 5G',
                'price' => 8490000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 4,
                'description' => 'Hiệu năng chiến game tốt với Snapdragon 7s Gen 2, sạc siêu tốc 67W làm đầy viên pin 5100mAh cực nhanh.',
                'stock' => 90, 'avg_rating' => 4.5,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'name' => 'Poco X6 Pro 5G',
                'price' => 7990000,
                'hinh_anh' => 'http://localhost/storage/iphone15.jpg',
                'category_id' => 4,
                'description' => 'Ông vua hiệu năng mới với vi xử lý Dimensity 8300 Ultra, chiến mượt mọi tựa game nặng nhất hiện nay.',
                'stock' => 110, 'avg_rating' => 4.7,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ];

        // Insert từng dòng để loại bỏ triệt để lỗi lệch cột dữ liệu
        foreach ($products as $product) {
            DB::table('products')->insert($product);
        }
    }
}