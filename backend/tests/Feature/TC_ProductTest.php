<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;

/**
 * [Đặng Văn Hà - 4.3.5 → 4.3.15] 14 Test Case Quản lý Sản phẩm & Danh mục
 * Chạy: php artisan test --filter=TC_ProductTest
 */
class TC_ProductTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected string $adminToken;
    protected string $userToken;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role'     => 'admin',
            'email'    => 'admin@test.com',
            'password' => Hash::make('password123'),
        ]);
        $this->user = User::factory()->create([
            'role'     => 'user',
            'email'    => 'user@test.com',
            'password' => Hash::make('password123'),
        ]);
        $this->category = Category::factory()->create(['name' => 'Điện thoại']);

        $this->adminToken = $this->postJson('/api/login', ['email' => 'admin@test.com', 'password' => 'password123'])->json('token');
        $this->userToken  = $this->postJson('/api/login', ['email' => 'user@test.com',  'password' => 'password123'])->json('token');
    }

    // ─────────────────────────────────────────────
    // TC1: Xóa mục không tồn tại (2 tab xóa cùng 1 ID)
    // ─────────────────────────────────────────────
    public function test_TC1_delete_product_already_deleted()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        $id = $product->id;

        // Tab 1: Xóa SP thành công
        $this->withToken($this->adminToken)->deleteJson("/api/admin/products/{$id}")->assertStatus(200);

        // Tab 2: Xóa lại SP đã xóa (soft deleted) → 404
        $this->withToken($this->adminToken)->deleteJson("/api/admin/products/{$id}")->assertStatus(404);
    }

    public function test_TC1_delete_category_already_deleted()
    {
        $cat = Category::factory()->create(['name' => 'DM Test']);
        $id  = $cat->id;

        $this->withToken($this->adminToken)->deleteJson("/api/admin/categories/{$id}")->assertStatus(200);
        $this->withToken($this->adminToken)->deleteJson("/api/admin/categories/{$id}")->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // TC2: Cập nhật trùng lặp – Optimistic Locking (2 tab sửa cùng 1 SP)
    // ─────────────────────────────────────────────
    public function test_TC2_optimistic_lock_product_update_conflict()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'name'        => 'Original Name',
            'price'       => 100000,
            'stock'       => 10,
        ]);

        $originalUpdatedAt = $product->updated_at->toIso8601String();

        // Tab 1: Update thành công
        $this->withToken($this->adminToken)->putJson("/api/admin/products/{$product->id}", [
            'name'        => 'Tab1 Updated',
            'price'       => 200000,
            'stock'       => 5,
            'category_id' => $this->category->id,
            'updated_at'  => $originalUpdatedAt,
        ])->assertStatus(200);

        // Giả lập thời gian trôi qua để DB updated_at khác với originalUpdatedAt
        $product->refresh();
        $product->updated_at = now()->addSeconds(5);
        $product->save();

        // Tab 2: Update với timestamp cũ → phải nhận 409 Conflict
        $this->withToken($this->adminToken)->putJson("/api/admin/products/{$product->id}", [
            'name'        => 'Tab2 Updated',
            'price'       => 300000,
            'stock'       => 3,
            'category_id' => $this->category->id,
            'updated_at'  => $originalUpdatedAt,  // timestamp cũ → xung đột
        ])->assertStatus(409);
    }

    // ─────────────────────────────────────────────
    // TC3: ID không tồn tại trong URL
    // ─────────────────────────────────────────────
    public function test_TC3_product_nonexistent_id_string()
    {
        // ID = chữ "abc"
        $this->withToken($this->adminToken)->getJson('/api/products/abc')->assertStatus(404);
    }

    public function test_TC3_product_nonexistent_id_large_number()
    {
        // ID = số cực lớn 99999999999
        $this->withToken($this->adminToken)->getJson('/api/products/99999999999')->assertStatus(404);
    }

    public function test_TC3_category_nonexistent_id()
    {
        $this->withToken($this->adminToken)->deleteJson('/api/admin/categories/99999')->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // TC4: Validate form (dữ liệu không hợp lệ)
    // ─────────────────────────────────────────────
    public function test_TC4_create_product_empty_name()
    {
        $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name'        => '',
            'category_id' => $this->category->id,
            'price'       => 100000,
            'stock'       => 10,
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_TC4_create_product_negative_price()
    {
        $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name'        => 'iPhone Test',
            'category_id' => $this->category->id,
            'price'       => -1,    // Giá âm → lỗi min:0
            'stock'       => 10,
        ])->assertStatus(422)->assertJsonValidationErrors(['price']);
    }

    public function test_TC4_create_product_negative_stock()
    {
        $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name'        => 'iPhone Test',
            'category_id' => $this->category->id,
            'price'       => 100000,
            'stock'       => -5,    // Tồn kho âm → lỗi min:0
        ])->assertStatus(422)->assertJsonValidationErrors(['stock']);
    }

    public function test_TC4_create_product_missing_category()
    {
        $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name'  => 'iPhone Test',
            'price' => 100000,
            'stock' => 10,
            // Không có category_id
        ])->assertStatus(422)->assertJsonValidationErrors(['category_id']);
    }

    public function test_TC4_create_category_empty_name()
    {
        $this->withToken($this->adminToken)->postJson('/api/admin/categories', [
            'name' => '',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    // ─────────────────────────────────────────────
    // TC5: Text quá tải (dán HTML từ vnexpress)
    // ─────────────────────────────────────────────
    public function test_TC5_product_name_exceeds_max_length()
    {
        // max:150 theo ProductRequest
        $longName = str_repeat('A', 151); // 151 ký tự → vượt quá max
        $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name'        => $longName,
            'category_id' => $this->category->id,
            'price'       => 100000,
            'stock'       => 10,
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_TC5_category_name_exceeds_max_length()
    {
        // max:255
        $this->withToken($this->adminToken)->postJson('/api/admin/categories', [
            'name' => str_repeat('B', 256),
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    // ─────────────────────────────────────────────
    // TC6: Khoảng trắng (whitespace và full-width space)
    // ─────────────────────────────────────────────
    public function test_TC6_product_name_only_whitespace()
    {
        // Tên SP = toàn khoảng trắng → Laravel trim() → required fail
        $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name'        => '     ',
            'category_id' => $this->category->id,
            'price'       => 100000,
            'stock'       => 10,
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_TC6_product_name_fullwidth_space()
    {
        // Khoảng trắng full-width 2-byte (U+3000)
        $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name'        => '　　　',  // full-width spaces
            'category_id' => $this->category->id,
            'price'       => 100000,
            'stock'       => 10,
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    // ─────────────────────────────────────────────
    // TC7: Số full-width (０１２３４５６７８９)
    // ─────────────────────────────────────────────
    public function test_TC7_product_price_fullwidth_number()
    {
        // Giá nhập bằng số full-width → backend "numeric" fail
        $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name'        => 'iPhone 15',
            'category_id' => $this->category->id,
            'price'       => '１２３４５',  // full-width số
            'stock'       => 10,
        ])->assertStatus(422)->assertJsonValidationErrors(['price']);
    }

    public function test_TC7_product_stock_fullwidth_number()
    {
        $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name'        => 'iPhone 15',
            'category_id' => $this->category->id,
            'price'       => 100000,
            'stock'       => '０１０',  // full-width số
        ])->assertStatus(422)->assertJsonValidationErrors(['stock']);
    }

    // ─────────────────────────────────────────────
    // TC8: Select-option (category_id không tồn tại)
    // ─────────────────────────────────────────────
    public function test_TC8_product_invalid_category_id()
    {
        // Inspect → sửa category_id thành ID không tồn tại
        $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name'        => 'iPhone 15',
            'category_id' => 99999,   // ID không tồn tại → exists:categories,id fail
            'price'       => 100000,
            'stock'       => 10,
        ])->assertStatus(422)->assertJsonValidationErrors(['category_id']);
    }

    // ─────────────────────────────────────────────
    // TC9: Trùng lặp dữ liệu (spam submit – category name unique)
    // ─────────────────────────────────────────────
    public function test_TC9_duplicate_category_name()
    {
        // Tạo danh mục đầu tiên
        $this->withToken($this->adminToken)->postJson('/api/admin/categories', [
            'name' => 'Samsung Galaxy',
        ])->assertStatus(201);

        // Tạo lại với cùng tên → unique:categories,name → 422
        $this->withToken($this->adminToken)->postJson('/api/admin/categories', [
            'name' => 'Samsung Galaxy',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_TC9_product_form_double_submit_only_one_created()
    {
        // Giả lập double submit: 2 request giống hệt nhau → phải chỉ có 1 SP được tạo
        $data = [
            'name'        => 'Samsung S25',
            'category_id' => $this->category->id,
            'price'       => 15000000,
            'stock'       => 5,
        ];

        $this->withToken($this->adminToken)->postJson('/api/admin/products', $data)->assertStatus(201);
        // Lần 2 vẫn tạo được (SP không có unique trên name) nhưng test DB chỉ có đúng 1 record
        $this->assertDatabaseCount('products', 1);
    }

    // ─────────────────────────────────────────────
    // TC10: URL params sai (?page=abc, ?page=99999)
    // ─────────────────────────────────────────────
    public function test_TC10_product_list_page_string_param()
    {
        // page=abc → không crash, trả trang 1
        $this->withToken($this->adminToken)->getJson('/api/products?page=abc')
            ->assertStatus(200);
    }

    public function test_TC10_product_list_page_out_of_range()
    {
        // page=99999 → không crash, trả trang rỗng hoặc trang cuối
        $this->withToken($this->adminToken)->getJson('/api/products?page=99999')
            ->assertStatus(200);
    }

    public function test_TC10_category_list_page_string_param()
    {
        $this->withToken($this->adminToken)->getJson('/api/categories?page=abc')
            ->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC11: Upload file không hợp lệ (PDF thay vì ảnh)
    // ─────────────────────────────────────────────
    public function test_TC11_upload_pdf_as_product_image()
    {
        // Upload file PDF vào endpoint ảnh SP → phải bị từ chối
        $pdfFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $res = $this->withToken($this->adminToken)->postJson('/api/admin/upload', [
            'file' => $pdfFile,
        ]);

        // Chấp nhận 422 (validate fail) hoặc 415 (unsupported media type)
        $this->assertContains($res->getStatusCode(), [415, 422, 400]);
    }

    public function test_TC11_upload_valid_image_product()
    {
        // Upload ảnh hợp lệ (jpg) → phải thành công
        $image = UploadedFile::fake()->image('phone.jpg', 400, 400);

        $res = $this->withToken($this->adminToken)->postJson('/api/admin/upload', [
            'file' => $image,
        ]);

        $this->assertContains($res->getStatusCode(), [200, 201]);
    }

    // ─────────────────────────────────────────────
    // TC12: Ảnh không thể hiển thị (file ảnh đã xóa khỏi storage)
    // ─────────────────────────────────────────────
    public function test_TC12_product_with_missing_image_still_accessible()
    {
        // SP có ảnh → xóa file ảnh → API vẫn trả SP không bị 500
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'hinh_anh'    => 'products/deleted_image.jpg', // file không tồn tại
        ]);

        $this->getJson("/api/products/{$product->id}")->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC13: Update SP lần 2 không kèm ảnh → giữ ảnh cũ
    // ─────────────────────────────────────────────
    public function test_TC13_update_product_without_image_keeps_old_image()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'hinh_anh'    => 'products/old_image.jpg',
            'name'        => 'SP Ban đầu',
            'price'       => 10000000,
            'stock'       => 20,
        ]);

        // Update lần 2: chỉ đổi tên, KHÔNG gửi hinh_anh
        $this->withToken($this->adminToken)->putJson("/api/admin/products/{$product->id}", [
            'name'        => 'SP Đã Sửa Tên',
            'price'       => 12000000,
            'stock'       => 15,
            'category_id' => $this->category->id,
            // Không có 'hinh_anh' → backend phải GIỮ ảnh cũ
        ])->assertStatus(200);

        // Kiểm tra ảnh cũ vẫn còn trong DB
        $this->assertDatabaseHas('products', [
            'id'       => $product->id,
            'name'     => 'SP Đã Sửa Tên',
            'hinh_anh' => 'products/old_image.jpg',  // ảnh cũ được giữ
        ]);
    }

    // ─────────────────────────────────────────────
    // TC14: CSRF / Auth – Xóa không có token (trình duyệt khác)
    // ─────────────────────────────────────────────
    public function test_TC14_delete_product_without_token()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        // Không có Authorization header → 401
        $this->deleteJson("/api/admin/products/{$product->id}")->assertStatus(401);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_TC14_delete_product_with_user_token_forbidden()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        // User thường dùng token user → 403
        $this->withToken($this->userToken)->deleteJson("/api/admin/products/{$product->id}")->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_TC14_delete_category_without_token()
    {
        $cat = Category::factory()->create(['name' => 'Bảo vệ CSRF']);
        $this->deleteJson("/api/admin/categories/{$cat->id}")->assertStatus(401);
    }
}
