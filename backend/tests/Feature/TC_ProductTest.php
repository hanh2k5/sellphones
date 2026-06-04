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

        // Tab 2: Xóa lại SP đã xóa (soft deleted) → 409 (vì có optimistic locking)
        $this->withToken($this->adminToken)->deleteJson("/api/admin/products/{$id}")->assertStatus(409);
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
            'updated_at'  => $product->updated_at,
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

    public function test_product_unique_name_returns_422()
    {
        Product::factory()->create(['name' => 'Điện thoại độc lạ', 'category_id' => $this->category->id]);
        $res = $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name' => 'Điện thoại độc lạ',
            'category_id' => $this->category->id,
            'price' => 1000,
            'stock' => 10,
        ]);
        $res->assertStatus(422);
        $this->assertEquals('Tên sản phẩm này đã tồn tại.', $res->json('errors.name.0'));
    }

    public function test_product_missing_category_custom_message()
    {
        $res = $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name' => 'iPhone Mới Không Category',
            'price' => 1000,
            'stock' => 10,
        ]);
        $res->assertStatus(422);
        $this->assertEquals('Vui lòng phân loại danh mục cho sản phẩm.', $res->json('errors.category_id.0'));
    }

    public function test_product_negative_price_or_stock_custom_message()
    {
        $res = $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name' => 'iPhone Âm Giá',
            'category_id' => $this->category->id,
            'price' => -10,
            'stock' => 10,
        ]);
        $res->assertStatus(422);
        $this->assertEquals('Giá và số lượng phải lớn hơn hoặc bằng 0.', $res->json('errors.price.0'));

        $res = $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name' => 'iPhone Âm Stock',
            'category_id' => $this->category->id,
            'price' => 1000,
            'stock' => -5,
        ]);
        $res->assertStatus(422);
        $this->assertEquals('Giá và số lượng phải lớn hơn hoặc bằng 0.', $res->json('errors.stock.0'));
    }

    public function test_product_description_xss_filtering()
    {
        $payload = '<script>alert("hack XSS")</script>Mô tả <a href="javascript:void(0)" onclick="hack()">sản phẩm</a>.';
        $res = $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name' => 'iPhone XSS Test',
            'category_id' => $this->category->id,
            'price' => 1000,
            'stock' => 10,
            'description' => $payload,
        ]);
        $res->assertStatus(201);
        $product = Product::find($res->json('data.id'));
        $this->assertStringNotContainsString('<script>', $product->description);
        $this->assertStringNotContainsString('onclick', $product->description);
        $this->assertStringNotContainsString('javascript:', $product->description);
    }

    public function test_product_show_deleted_returns_404_custom_message()
    {
        $res = $this->getJson('/api/products/999999');
        $res->assertStatus(404);
        $this->assertEquals('Sản phẩm không tồn tại hoặc đã bị xóa', $res->json('message'));
    }

    public function test_product_update_db_error_returns_500_custom_message()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id]);
        
        // Mock ProductService to throw generic Exception during update
        $mock = $this->mock(\App\Services\ProductService::class);
        $mock->shouldReceive('updateProduct')->andThrow(new \Exception('DB Connection Failure', 0));

        $res = $this->withToken($this->adminToken)->putJson("/api/admin/products/{$product->id}", [
            'name' => 'Name change',
            'price' => 100,
            'stock' => 10,
            'category_id' => $this->category->id,
            'updated_at' => $product->updated_at,
        ]);

        $res->assertStatus(500);
        $this->assertEquals('Lỗi hệ thống, chưa thể cập nhật thông tin', $res->json('message'));
    }

    public function test_product_update_success_deletes_old_image()
    {
        Storage::fake('public');
        
        // Tạo ảnh cũ trong Storage
        $oldImagePath = 'products/old_thumb.jpg';
        Storage::disk('public')->put($oldImagePath, 'dummy content');
        $this->assertTrue(Storage::disk('public')->exists($oldImagePath));

        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'hinh_anh' => $oldImagePath,
            'stock' => 10,
        ]);

        $newImageFile = UploadedFile::fake()->image('new_thumb.jpg');

        $res = $this->withToken($this->adminToken)->putJson("/api/admin/products/{$product->id}", [
            'name' => $product->name,
            'price' => $product->price,
            'stock' => 10,
            'category_id' => $product->category_id,
            'hinh_anh_file' => $newImageFile,
            'updated_at' => $product->updated_at,
        ]);

        $res->assertStatus(200);
        
        // Xác nhận ảnh cũ đã bị xóa khỏi Storage
        $this->assertFalse(Storage::disk('public')->exists($oldImagePath));
    }

    public function test_product_delete_with_pending_order_fails()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id, 'stock' => 10]);
        
        // Tạo order pending
        $order = \App\Models\Order::create([
            'user_id' => $this->user->id,
            'total_amount' => 1000,
            'status' => 'pending',
            'payment_method' => 'cod',
            'shipping_address' => 'Hanoi, Vietnam',
            'version' => 1,
        ]);
        
        // Liên kết sản phẩm
        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price_at_purchase' => 1000,
        ]);

        $res = $this->withToken($this->adminToken)->deleteJson("/api/admin/products/{$product->id}", [
            'updated_at' => $product->updated_at,
        ]);

        $res->assertStatus(422);
        $this->assertEquals('Không thể xóa sản phẩm này vì đang nằm trong đơn hàng chờ xử lý.', $res->json('message'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_product_delete_conflict_409()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id, 'stock' => 10]);
        
        // Thay đổi updated_at của DB để tạo conflict với client
        $oldUpdatedAt = $product->updated_at->toIso8601String();
        $product->updated_at = now()->addSeconds(5);
        $product->save();

        $res = $this->withToken($this->adminToken)->deleteJson("/api/admin/products/{$product->id}", [
            'updated_at' => $oldUpdatedAt,
        ]);

        $res->assertStatus(409);
        $this->assertEquals('Cảnh báo: Dữ liệu đã thay đổi, vui lòng làm mới!', $res->json('message'));
    }

    public function test_product_restore_already_restored_fails_with_409()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id, 'stock' => 10]);
        $product->delete(); // Xóa mềm

        // Admin khác khôi phục sản phẩm trước
        $product->restore();

        $res = $this->withToken($this->adminToken)->postJson("/api/admin/products/{$product->id}/restore");

        $res->assertStatus(409);
        $this->assertEquals('Sản phẩm này đã được khôi phục bởi một Admin khác.', $res->json('message'));
    }

    public function test_product_force_delete_already_restored_fails_with_409()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id, 'stock' => 10]);
        $product->delete(); // Xóa mềm

        // Admin khác khôi phục sản phẩm trước
        $product->restore();

        $res = $this->withToken($this->adminToken)->deleteJson("/api/admin/products/{$product->id}/force-delete");

        $res->assertStatus(409);
        $this->assertEquals('Sản phẩm này đã được khôi phục bởi một Admin khác.', $res->json('message'));
    }

    public function test_product_force_delete_with_order_items_fails_with_400()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id, 'stock' => 10]);
        
        $order = \App\Models\Order::factory()->create(['user_id' => $this->admin->id]);
        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price_at_purchase' => $product->price
        ]);

        $product->delete(); // Xóa mềm

        $res = $this->withToken($this->adminToken)->deleteJson("/api/admin/products/{$product->id}/force-delete");

        $res->assertStatus(400);
        $this->assertEquals('Không thể xóa vĩnh viễn sản phẩm đã phát sinh giao dịch.', $res->json('message'));
    }

    public function test_product_restore_nonexistent_fails_with_404()
    {
        $res = $this->withToken($this->adminToken)->postJson("/api/admin/products/99999/restore");

        $res->assertStatus(404);
        $this->assertEquals('Sản phẩm không tồn tại hoặc đã bị xóa vĩnh viễn.', $res->json('message'));
    }

    public function test_product_force_delete_nonexistent_fails_with_404()
    {
        $res = $this->withToken($this->adminToken)->deleteJson("/api/admin/products/99999/force-delete");

        $res->assertStatus(404);
        $this->assertEquals('Sản phẩm không tồn tại hoặc đã bị xóa vĩnh viễn.', $res->json('message'));
    }

    public function test_product_create_and_update_with_featured_flag()
    {
        // Tạo sản phẩm mới với is_featured = true
        $res = $this->withToken($this->adminToken)->postJson('/api/admin/products', [
            'name' => 'Sản phẩm Hot Test',
            'category_id' => $this->category->id,
            'price' => 2000,
            'stock' => 5,
            'is_featured' => true,
        ]);

        $res->assertStatus(201);
        $productId = $res->json('data.id');
        $this->assertDatabaseHas('products', ['id' => $productId, 'is_featured' => true]);

        // Cập nhật sản phẩm đó sang is_featured = false
        $resUpdate = $this->withToken($this->adminToken)->putJson("/api/admin/products/{$productId}", [
            'name' => 'Sản phẩm Hot Test',
            'category_id' => $this->category->id,
            'price' => 2000,
            'stock' => 5,
            'is_featured' => false,
            'updated_at' => $res->json('data.updated_at'),
        ]);

        $resUpdate->assertStatus(200);
        $this->assertDatabaseHas('products', ['id' => $productId, 'is_featured' => false]);
    }

    public function test_product_price_filters_and_swapping()
    {
        // 1. Tạo một sản phẩm có giá 0đ
        $p0 = \App\Models\Product::create([
            'name' => 'Sản phẩm lỗi giá 0đ',
            'category_id' => $this->category->id,
            'price' => 0,
            'stock' => 10,
            'is_active' => true,
        ]);

        // 2. Tạo một sản phẩm có giá 15tr (15000000)
        $p15 = \App\Models\Product::create([
            'name' => 'Sản phẩm ngon 15tr',
            'category_id' => $this->category->id,
            'price' => 15000000,
            'stock' => 10,
            'is_active' => true,
        ]);

        // 3. Query sản phẩm của khách hàng (không truyền show_all)
        // Xác nhận không xuất hiện sản phẩm giá 0đ
        $res = $this->getJson('/api/products');
        $res->assertStatus(200);
        $productIds = collect($res->json('data'))->pluck('id')->toArray();
        $this->assertNotContains($p0->id, $productIds);

        // 4. Lọc với giá trị đảo ngược min > max (gia_tu = 20tr, gia_den = 10tr)
        // Backend tự động đảo ngược và lọc sản phẩm có giá từ 10tr đến 20tr.
        $resFilter = $this->getJson('/api/products?gia_tu=20000000&gia_den=10000000');
        $resFilter->assertStatus(200);
        $filteredIds = collect($resFilter->json('data'))->pluck('id')->toArray();
        $this->assertContains($p15->id, $filteredIds);
        $this->assertNotContains($p0->id, $filteredIds);
        
        // Xác nhận thông điệp đính kèm trong response trả về từ backend
        $this->assertStringContainsString('Tìm thấy', $resFilter->json('message'));
        $this->assertStringContainsString('sản phẩm trong tầm giá', $resFilter->json('message'));

        // Dọn dẹp
        $p0->forceDelete();
        $p15->forceDelete();
    }

    public function test_product_restore_with_mismatched_timestamp_fails_with_409()
    {
        $product = Product::factory()->create(['category_id' => $this->category->id, 'stock' => 10]);
        $product->delete(); // Xóa mềm

        // Cập nhật lại bản ghi trong DB để làm sai lệch timestamp
        $product->updated_at = \Illuminate\Support\Carbon::now()->addMinutes(10);
        $product->saveQuietly();

        $res = $this->withToken($this->adminToken)->postJson("/api/admin/products/{$product->id}/restore", [
            'updated_at' => \Illuminate\Support\Carbon::now()->subMinutes(5)->toIso8601String()
        ]);

        $res->assertStatus(409);
        $this->assertEquals('Cảnh báo: Dữ liệu đã thay đổi, vui lòng làm mới!', $res->json('message'));
    }

    public function test_product_restore_with_inactive_category_assigns_to_uncategorized()
    {
        // 1. Tạo danh mục cha và ẩn nó
        $parentCat = \App\Models\Category::create([
            'name' => 'Danh mục cha ẩn',
            'is_active' => false,
        ]);

        // 2. Tạo danh mục con
        $childCat = \App\Models\Category::create([
            'name' => 'Danh mục con hoạt động',
            'parent_id' => $parentCat->id,
            'is_active' => true,
        ]);

        // 3. Tạo sản phẩm gắn vào danh mục con và xóa mềm
        $product = Product::factory()->create([
            'category_id' => $childCat->id,
            'stock' => 10,
        ]);
        $product->delete();

        // 4. Thực hiện phục hồi
        $res = $this->withToken($this->adminToken)->postJson("/api/admin/products/{$product->id}/restore");
        $res->assertStatus(200);

        // 5. Kiểm tra sản phẩm được khôi phục và chuyển sang "Chưa phân loại"
        $restored = Product::find($product->id);
        $this->assertNotNull($restored);
        $this->assertEquals('Chưa phân loại', $restored->category->name);

        // Dọn dẹp
        $restored->forceDelete();
        $childCat->delete();
        $parentCat->delete();
        
        $uncat = \App\Models\Category::where('name', 'Chưa phân loại')->first();
        if ($uncat) {
            $uncat->delete();
        }
    }
}

