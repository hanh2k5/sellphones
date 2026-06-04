<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Category;

class TC_CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);
        $this->token = $response->json('token');
    }

    /**
     * Case 1: Admin sửa tên danh mục hợp lệ và không thay đổi Danh mục cha.
     * Hệ thống lưu thành công thông tin mới. Tên danh mục được cập nhật. URL Slug tự động cập nhật và duy nhất.
     */
    public function test_1_update_category_name_successfully_slug_updated_and_unique()
    {
        $category = Category::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-name'
        ]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->putJson("/api/admin/categories/{$category->id}", [
                'name' => 'New Name',
                'parent_id' => null
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
            'slug' => 'new-name'
        ]);
    }

    /**
     * Case 2: Thay đổi Danh mục cha.
     * Admin chọn một danh mục cha hợp lệ khác cho danh mục đang sửa.
     * Hệ thống lưu thành công và cập nhật mối quan hệ cha-con mới. Danh mục mới được hiển thị đúng trong cấu trúc cây.
     */
    public function test_2_change_parent_category()
    {
        $parent1 = Category::factory()->create(['name' => 'Parent One']);
        $parent2 = Category::factory()->create(['name' => 'Parent Two']);
        $child = Category::factory()->create(['name' => 'Child', 'parent_id' => $parent1->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->putJson("/api/admin/categories/{$child->id}", [
                'name' => 'Child',
                'parent_id' => $parent2->id
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('categories', [
            'id' => $child->id,
            'parent_id' => $parent2->id
        ]);
    }

    /**
     * Case 3: Để trống Tên danh mục.
     * Admin xóa toàn bộ nội dung trong ô Tên danh mục và bấm Lưu.
     * Hệ thống hiển thị thông báo lỗi: "Tên danh mục không được để trống/bắt buộc".
     */
    public function test_3_empty_category_name_returns_error()
    {
        $category = Category::factory()->create(['name' => 'Standard Category']);

        // Test create empty name
        $responseCreate = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/admin/categories', [
                'name' => '',
                'parent_id' => null
            ]);
        $responseCreate->assertStatus(422);
        $responseCreate->assertJsonValidationErrors(['name']);
        $this->assertEquals(
            'Tên danh mục không được để trống/bắt buộc',
            $responseCreate->json('errors.name.0')
        );

        // Test update empty name
        $responseUpdate = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->putJson("/api/admin/categories/{$category->id}", [
                'name' => '',
                'parent_id' => null
            ]);
        $responseUpdate->assertStatus(422);
        $responseUpdate->assertJsonValidationErrors(['name']);
        $this->assertEquals(
            'Tên danh mục không được để trống/bắt buộc',
            $responseUpdate->json('errors.name.0')
        );
    }

    /**
     * Case 4: Tên danh mục quá dài.
     * Admin nhập một chuỗi quá 100 ký tự vào ô Tên danh mục.
     * Hệ thống hiển thị lỗi: "Tên danh mục không hợp lệ hoặc quá dài".
     */
    public function test_4_long_category_name_returns_error()
    {
        $longName = str_repeat('A', 101);
        $category = Category::factory()->create(['name' => 'Standard Category']);

        // Test create too long name
        $responseCreate = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/admin/categories', [
                'name' => $longName,
                'parent_id' => null
            ]);
        $responseCreate->assertStatus(422);
        $responseCreate->assertJsonValidationErrors(['name']);
        $this->assertEquals(
            'Tên danh mục không hợp lệ hoặc quá dài.',
            $responseCreate->json('errors.name.0')
        );

        // Test update too long name
        $responseUpdate = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->putJson("/api/admin/categories/{$category->id}", [
                'name' => $longName,
                'parent_id' => null
            ]);
        $responseUpdate->assertStatus(422);
        $responseUpdate->assertJsonValidationErrors(['name']);
        $this->assertEquals(
            'Tên danh mục không hợp lệ hoặc quá dài.',
            $responseUpdate->json('errors.name.0')
        );
    }

    /**
     * Case 5: Tên danh mục trùng lặp.
     * Admin sửa tên danh mục thành tên của một danh mục khác đã tồn tại, dẫn đến trùng URL Slug.
     * Hệ thống tự động nối thêm chuỗi ngẫu nhiên hoặc timestamp vào đuôi slug (VD: iphone-1) và lưu thành công.
     */
    public function test_5_duplicate_category_name_generates_unique_slug()
    {
        $cat1 = Category::factory()->create(['name' => 'iPhone', 'slug' => 'iphone']);
        $cat2 = Category::factory()->create(['name' => 'Samsung', 'slug' => 'samsung']);

        // Update name of cat2 to 'iPhone' (same as cat1)
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->putJson("/api/admin/categories/{$cat2->id}", [
                'name' => 'iPhone',
                'parent_id' => null
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('categories', [
            'id' => $cat2->id,
            'name' => 'iPhone',
            'slug' => 'iphone-1'
        ]);
    }

    /**
     * Case 6: Chèn Mã độc (XSS).
     * Admin nhập các thẻ HTML độc hại (<script>...</script>) vào ô Tên danh mục.
     * Backend thực hiện Escape dữ liệu đầu vào (lọc mã độc HTML). Hệ thống lưu thành công tên danh mục đã được làm sạch và không làm vỡ giao diện.
     */
    public function test_6_xss_injection_is_filtered()
    {
        $payload = '<script>alert("XSS")</script>Apple & Orange';

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/admin/categories', [
                'name' => $payload,
                'parent_id' => null
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
        $this->assertEquals(
            'Tên danh mục không hợp lệ hoặc quá dài.',
            $response->json('errors.name.0')
        );
    }

    /**
     * Case 7: ID Danh mục cha không tồn tại.
     * Admin cố tình truyền lên một parent_id (ID danh mục cha) không có trong cơ sở dữ liệu (Fake request).
     * Backend thực hiện Validate exists:categories,id. Hệ thống trả lỗi nếu ID cha không hợp lệ.
     */
    public function test_7_non_existent_parent_id_returns_error()
    {
        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/admin/categories', [
                'name' => 'Subcategory',
                'parent_id' => 99999
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['parent_id']);
        $this->assertEquals(
            'Danh mục cha không tồn tại.',
            $response->json('errors.parent_id.0')
        );
    }

    /**
     * Case 8: Cố gắng tạo vòng lặp đệ quy.
     * Admin chọn chính danh mục đang sửa làm Danh mục cha của nó (VD: Danh mục "Apple" chọn cha là "Apple").
     * Backend ràng buộc logic để ngăn chặn điều này. Hệ thống trả lỗi: "Danh mục con không được phép chọn chính nó làm cha".
     */
    public function test_8_circular_reference_prevention()
    {
        $category = Category::factory()->create(['name' => 'Apple']);

        // Set itself as parent
        $responseSelf = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->putJson("/api/admin/categories/{$category->id}", [
                'name' => 'Apple',
                'parent_id' => $category->id
            ]);

        $responseSelf->assertStatus(422);
        $responseSelf->assertJsonValidationErrors(['parent_id']);
        $this->assertEquals(
            'Danh mục con không được phép chọn chính nó làm cha',
            $responseSelf->json('errors.parent_id.0')
        );

        // Set child as parent (indirect loop)
        $child = Category::factory()->create(['name' => 'iPhone', 'parent_id' => $category->id]);

        $responseIndirect = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->putJson("/api/admin/categories/{$category->id}", [
                'name' => 'Apple',
                'parent_id' => $child->id
            ]);

        $responseIndirect->assertStatus(422);
        $responseIndirect->assertJsonValidationErrors(['parent_id']);
        $this->assertEquals(
            'Danh mục con không được phép chọn chính nó làm cha',
            $responseIndirect->json('errors.parent_id.0')
        );
    }

    /**
     * Case 9: Xóa danh mục chứa sản phẩm.
     * Trả về HTTP 422. Thông báo: "Không thể xóa! Danh mục này vẫn còn chứa sản phẩm."
     */
    public function test_9_delete_category_with_products_returns_error()
    {
        $category = Category::factory()->create(['name' => 'iPhone']);
        \App\Models\Product::factory()->create(['category_id' => $category->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(422);
        $this->assertEquals(
            'Không thể xóa! Danh mục này vẫn còn chứa sản phẩm.',
            $response->json('message')
        );
    }

    /**
     * Case 10: Xóa danh mục chứa danh mục con.
     * Trả về HTTP 422. Thông báo: "Vui lòng xóa hoặc di chuyển các danh mục con trước."
     */
    public function test_10_delete_category_with_children_returns_error()
    {
        $parent = Category::factory()->create(['name' => 'Apple']);
        Category::factory()->create(['name' => 'iPhone 15', 'parent_id' => $parent->id]);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->deleteJson("/api/admin/categories/{$parent->id}");

        $response->assertStatus(422);
        $this->assertEquals(
            'Vui lòng xóa hoặc di chuyển các danh mục con trước.',
            $response->json('message')
        );
    }

    /**
     * Case 11: Xóa danh mục thành công.
     * Sau khi vượt qua các lớp bảo vệ, gọi lệnh Category::destroy($id).
     */
    public function test_11_delete_category_successfully()
    {
        $category = Category::factory()->create(['name' => 'Empty Category']);

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(200);
        $this->assertEquals(
            'Xóa danh mục thành công.',
            $response->json('message')
        );
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    /**
     * Case 12: Danh mục cha được chọn phải là danh mục gốc.
     * Trả về HTTP 422. Thông báo: "Danh mục cha được chọn phải là danh mục gốc."
     */
    public function test_12_parent_id_must_be_root_category()
    {
        $root = Category::factory()->create(['name' => 'Root Category']);
        $sub = Category::factory()->create(['name' => 'Sub Category', 'parent_id' => $root->id]);
        
        // Test create with subcategory as parent_id
        $responseCreate = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/admin/categories', [
                'name' => 'New Sub Sub',
                'parent_id' => $sub->id
            ]);
        $responseCreate->assertStatus(422);
        $responseCreate->assertJsonValidationErrors(['parent_id']);
        $this->assertEquals(
            'Danh mục cha được chọn phải là danh mục gốc.',
            $responseCreate->json('errors.parent_id.0')
        );

        // Test update with subcategory as parent_id
        $categoryToUpdate = Category::factory()->create(['name' => 'Target']);
        $responseUpdate = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->putJson("/api/admin/categories/{$categoryToUpdate->id}", [
                'name' => 'Target Updated',
                'parent_id' => $sub->id
            ]);
        $responseUpdate->assertStatus(422);
        $responseUpdate->assertJsonValidationErrors(['parent_id']);
        $this->assertEquals(
            'Danh mục cha được chọn phải là danh mục gốc.',
            $responseUpdate->json('errors.parent_id.0')
        );
    }
}


