<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Review;

/**
 * [Đặng Văn Hà - 4.3.16 → 4.3.18] 14 Test Case Đánh giá Sản phẩm
 * Chạy: php artisan test --filter=TC_ReviewTest
 */
class TC_ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected User $otherUser;
    protected string $adminToken;
    protected string $userToken;
    protected string $otherToken;
    protected Product $product;
    protected Order $completedOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin     = User::factory()->create(['role' => 'admin', 'email' => 'admin@test.com', 'password' => Hash::make('password123')]);
        $this->user      = User::factory()->create(['role' => 'user',  'email' => 'user@test.com',  'password' => Hash::make('password123')]);
        $this->otherUser = User::factory()->create(['role' => 'user',  'email' => 'other@test.com', 'password' => Hash::make('password123')]);

        $category       = Category::factory()->create(['name' => 'Điện thoại']);
        $this->product  = Product::factory()->create(['category_id' => $category->id, 'is_active' => true]);

        // Tạo đơn hàng completed để user được quyền đánh giá
        $this->completedOrder = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'completed',
        ]);
        $this->completedOrder->items()->create([
            'product_id'        => $this->product->id,
            'quantity'          => 1,
            'price_at_purchase' => $this->product->price,
        ]);

        $this->adminToken = $this->postJson('/api/login', ['email' => 'admin@test.com', 'password' => 'password123'])->json('token');
        $this->userToken  = $this->postJson('/api/login', ['email' => 'user@test.com',  'password' => 'password123'])->json('token');
        $this->otherToken = $this->postJson('/api/login', ['email' => 'other@test.com', 'password' => 'password123'])->json('token');
    }

    // ─────────────────────────────────────────────
    // TC1: Xóa mục không tồn tại (2 tab xóa cùng review)
    // ─────────────────────────────────────────────
    public function test_TC1_delete_review_already_deleted()
    {
        $review = Review::factory()->create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'order_id'   => $this->completedOrder->id,
            'rating'     => 5,
            'comment'    => 'Rất tốt!',
            'status'     => 'approved',
        ]);
        $id = $review->id;

        // Tab 1: Xóa thành công
        $this->withToken($this->adminToken)->deleteJson("/api/reviews/{$id}")->assertStatus(200);

        // Tab 2: Xóa lại → 404
        $this->withToken($this->adminToken)->deleteJson("/api/reviews/{$id}")->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // TC2: Cập nhật trùng lặp (Admin duyệt 2 tab)
    // ─────────────────────────────────────────────
    public function test_TC2_moderate_review_both_tabs_succeed()
    {
        $review = Review::factory()->create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'order_id'   => $this->completedOrder->id,
            'status'     => 'pending',
        ]);

        // Tab 1: Approve
        $this->withToken($this->adminToken)->putJson("/api/admin/reviews/{$review->id}/moderate", [
            'status' => 'approved',
        ])->assertStatus(200);

        // Tab 2: Hidden (ghi đè lần 2 vẫn được vì review không có Optimistic Lock)
        $this->withToken($this->adminToken)->putJson("/api/admin/reviews/{$review->id}/moderate", [
            'status' => 'hidden',
        ])->assertStatus(200);

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'status' => 'hidden']);
    }

    // ─────────────────────────────────────────────
    // TC3: ID không tồn tại
    // ─────────────────────────────────────────────
    public function test_TC3_review_nonexistent_id_string()
    {
        $this->withToken($this->adminToken)->deleteJson('/api/reviews/abc')->assertStatus(404);
    }

    public function test_TC3_review_nonexistent_id_large_number()
    {
        $this->withToken($this->adminToken)->deleteJson('/api/reviews/99999999999')->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // TC4: Validate form (đánh giá không hợp lệ)
    // ─────────────────────────────────────────────
    public function test_TC4_create_review_missing_rating()
    {
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'order_id' => $this->completedOrder->id,
            'comment'  => 'Hay lắm!',
            // Không có rating
        ])->assertStatus(422)->assertJsonValidationErrors(['rating']);
    }

    public function test_TC4_create_review_missing_comment()
    {
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'order_id' => $this->completedOrder->id,
            'rating'   => 5,
            // Không có comment
        ])->assertStatus(422)->assertJsonValidationErrors(['comment']);
    }

    public function test_TC4_create_review_missing_order_id()
    {
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'rating'  => 4,
            'comment' => 'OK',
            // Không có order_id
        ])->assertStatus(422)->assertJsonValidationErrors(['order_id']);
    }

    public function test_TC4_moderate_review_invalid_status()
    {
        $review = Review::factory()->create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'order_id'   => $this->completedOrder->id,
            'status'     => 'pending',
        ]);

        $this->withToken($this->adminToken)->putJson("/api/admin/reviews/{$review->id}/moderate", [
            'status' => 'deleted',  // không hợp lệ → in:approved,hidden
        ])->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    // ─────────────────────────────────────────────
    // TC5: Text quá tải (comment > 1000 ký tự)
    // ─────────────────────────────────────────────
    public function test_TC5_review_comment_exceeds_max_length()
    {
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'order_id' => $this->completedOrder->id,
            'rating'   => 5,
            'comment'  => str_repeat('A', 1001),  // > max:1000
        ])->assertStatus(422)->assertJsonValidationErrors(['comment']);
    }

    // ─────────────────────────────────────────────
    // TC6: Khoảng trắng (comment toàn khoảng trắng)
    // ─────────────────────────────────────────────
    public function test_TC6_review_comment_whitespace_only()
    {
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'order_id' => $this->completedOrder->id,
            'rating'   => 5,
            'comment'  => '          ',  // toàn khoảng trắng
        ])->assertStatus(422)->assertJsonValidationErrors(['comment']);
    }

    public function test_TC6_review_comment_fullwidth_space()
    {
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'order_id' => $this->completedOrder->id,
            'rating'   => 5,
            'comment'  => '　　　　',  // full-width spaces only
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC7: Số full-width (rating)
    // ─────────────────────────────────────────────
    public function test_TC7_review_rating_fullwidth_number()
    {
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'order_id' => $this->completedOrder->id,
            'rating'   => '５',  // full-width số → không phải integer
            'comment'  => 'Hay!',
        ])->assertStatus(422)->assertJsonValidationErrors(['rating']);
    }

    // ─────────────────────────────────────────────
    // TC8: Select-option (rating ngoài range 1-5)
    // ─────────────────────────────────────────────
    public function test_TC8_review_rating_out_of_range_high()
    {
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'order_id' => $this->completedOrder->id,
            'rating'   => 6,  // max:5 → fail
            'comment'  => 'Hay!',
        ])->assertStatus(422)->assertJsonValidationErrors(['rating']);
    }

    public function test_TC8_review_rating_out_of_range_low()
    {
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'order_id' => $this->completedOrder->id,
            'rating'   => 0,  // min:1 → fail
            'comment'  => 'Hay!',
        ])->assertStatus(422)->assertJsonValidationErrors(['rating']);
    }

    // ─────────────────────────────────────────────
    // TC9: Trùng lặp – User đánh giá cùng SP 2 lần
    // ─────────────────────────────────────────────
    public function test_TC9_cannot_review_same_product_twice()
    {
        // Lần 1: đánh giá thành công
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'order_id' => $this->completedOrder->id,
            'rating'   => 5,
            'comment'  => 'Rất tốt!',
        ])->assertStatus(201);

        // Lần 2: đánh giá lại cùng SP → phải bị chặn
        $this->withToken($this->userToken)->postJson("/api/products/{$this->product->id}/reviews", [
            'order_id' => $this->completedOrder->id,
            'rating'   => 3,
            'comment'  => 'Lần 2',
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC10: URL params sai
    // ─────────────────────────────────────────────
    public function test_TC10_admin_reviews_page_string_param()
    {
        $this->withToken($this->adminToken)->getJson('/api/admin/reviews?page=abc')->assertStatus(200);
    }

    public function test_TC10_admin_reviews_rating_invalid()
    {
        // Lọc theo rating=abc → không crash
        $this->withToken($this->adminToken)->getJson('/api/admin/reviews?rating=abc')->assertStatus(200);
    }

    public function test_admin_can_filter_reviews_by_status()
    {
        Review::factory()->create([
            'user_id' => $this->user->id,
            'product_id' => $this->product->id,
            'order_id' => $this->completedOrder->id,
            'status' => 'approved',
            'comment' => 'Được duyệt',
        ]);

        $hiddenOrder = Order::factory()->create([
            'user_id' => $this->otherUser->id,
            'status' => 'completed',
        ]);
        $hiddenOrder->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price_at_purchase' => $this->product->price,
        ]);

        Review::factory()->create([
            'user_id' => $this->otherUser->id,
            'product_id' => $this->product->id,
            'order_id' => $hiddenOrder->id,
            'status' => 'hidden',
            'comment' => 'Đã ẩn',
        ]);

        $res = $this->withToken($this->adminToken)
            ->getJson('/api/admin/reviews?status=hidden')
            ->assertStatus(200)
            ->assertJsonCount(1, 'data');

        $this->assertSame('hidden', $res->json('data.0.status'));
    }

    // ─────────────────────────────────────────────
    // TC11: Upload file (N/A – Review không có upload ảnh)
    // ─────────────────────────────────────────────
    public function test_TC11_not_applicable_review_has_no_upload()
    {
        $this->assertTrue(true, 'TC11: Review không có upload ảnh – không áp dụng.');
    }

    // ─────────────────────────────────────────────
    // TC12: Ảnh SP bị xóa – Review vẫn hiển thị được
    // ─────────────────────────────────────────────
    public function test_TC12_product_image_missing_review_still_shows()
    {
        $product = Product::factory()->create([
            'category_id' => Category::factory()->create()->id,
            'hinh_anh'    => 'products/deleted.jpg',  // file không tồn tại
            'is_active'   => true,
        ]);

        Review::factory()->create([
            'user_id'    => $this->user->id,
            'product_id' => $product->id,
            'order_id'   => $this->completedOrder->id,
            'status'     => 'approved',
        ]);

        $this->getJson("/api/products/{$product->id}")->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC13: N/A – Review không có upload ảnh
    // ─────────────────────────────────────────────
    public function test_TC13_not_applicable_review_no_image_upload()
    {
        $this->assertTrue(true, 'TC13: Review không có upload ảnh – không áp dụng.');
    }

    // ─────────────────────────────────────────────
    // TC14: CSRF / Auth – Xóa review không có token
    // ─────────────────────────────────────────────
    public function test_TC14_delete_review_without_token()
    {
        $review = Review::factory()->create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'order_id'   => $this->completedOrder->id,
            'status'     => 'approved',
        ]);

        // Không có token → 401
        $this->deleteJson("/api/reviews/{$review->id}")->assertStatus(401);
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_TC14_delete_other_users_review_forbidden()
    {
        $review = Review::factory()->create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'order_id'   => $this->completedOrder->id,
            'status'     => 'approved',
        ]);

        // User khác thử xóa review của user1 → 403
        $this->withToken($this->otherToken)->deleteJson("/api/reviews/{$review->id}")->assertStatus(403);
        $this->assertDatabaseHas('reviews', ['id' => $review->id]);
    }

    public function test_TC14_moderate_review_without_admin_token()
    {
        $review = Review::factory()->create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'order_id'   => $this->completedOrder->id,
            'status'     => 'pending',
        ]);

        // User thường thử moderate review → 403
        $this->withToken($this->userToken)->putJson("/api/admin/reviews/{$review->id}/moderate", [
            'status' => 'approved',
        ])->assertStatus(403);
    }
}
