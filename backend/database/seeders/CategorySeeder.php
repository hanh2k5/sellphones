<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
       DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('categories')->truncate();
      DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Danh mục cha
        DB::table('categories')->insert([
            ['id' => 1, 'parent_id' => null, 'name' => 'iPhone', 'slug' => 'iphone', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'parent_id' => null, 'name' => 'Samsung', 'slug' => 'samsung', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'parent_id' => null, 'name' => 'Oppo', 'slug' => 'oppo', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'parent_id' => null, 'name' => 'Xiaomi', 'slug' => 'xiaomi', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'parent_id' => null, 'name' => 'Phụ kiện', 'slug' => 'phu-kien', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Danh mục con (ví dụ)
        DB::table('categories')->insert([
            ['id' => 6, 'parent_id' => 5, 'name' => 'Ốp lưng', 'slug' => 'op-lung', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 7, 'parent_id' => 5, 'name' => 'Sạc & Cáp', 'slug' => 'sac-cap', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 8, 'parent_id' => 5, 'name' => 'Tai nghe', 'slug' => 'tai-nghe', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
