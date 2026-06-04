<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Review;
use App\Models\Order;
use Carbon\Carbon;

class MassiveProductReviewTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $product;
    protected $category;
    protected $order;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'email' => 'review@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);
        
        $response = $this->postJson('/api/login', [
            'email' => 'review@example.com',
            'password' => 'password123',
        ]);
        $this->token = $response->json('token');
        
        $this->category = Category::factory()->create(['name' => 'Phones']);
        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'name' => 'Test Phone',
            'is_active' => true,
        ]);
        
        // Tạo order đã hoàn thành để có quyền review
        $this->order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'completed']);
        $this->order->items()->create(['product_id' => $this->product->id, 'quantity' => 1, 'price_at_purchase' => 10000]);
    }

    // ==========================================
    // 20 TRƯỜNG HỢP NGƯỜI DÙNG SỬ DỤNG ĐÚNG MỤC ĐÍCH
    // ==========================================

    public function test_guest_can_view_product_list()
    {
        $this->getJson('/api/products')->assertStatus(200)->assertJsonPath('data.0.name', 'Test Phone');
    }

    public function test_guest_can_view_single_product_details()
    {
        $this->getJson("/api/products/{$this->product->id}")->assertStatus(200)->assertJsonPath('name', 'Test Phone');
    }

    public function test_guest_cannot_view_inactive_product()
    {
        $inactive = Product::factory()->create(['is_active' => false]);
        $this->getJson("/api/products/{$inactive->id}")->assertStatus(404);
    }

    public function test_guest_can_filter_products_by_category()
    {
        $this->getJson("/api/products?category={$this->category->id}")->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_guest_can_search_products_by_name()
    {
        $this->getJson('/api/products?search=Test')->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_guest_can_sort_products_by_price_asc()
    {
        Product::factory()->create(['category_id' => $this->category->id, 'price' => 5000, 'is_active' => true]);
        $res = $this->getJson('/api/products?sort_by=price&sort_dir=asc')->assertStatus(200);
        $this->assertEquals(5000, $res->json('data.0.price'));
    }

    public function test_user_can_view_product_reviews()
    {
        Review::factory()->create(['product_id' => $this->product->id, 'status' => 'approved']);
        $this->getJson("/api/products/{$this->product->id}/reviews")->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_user_cannot_view_hidden_reviews()
    {
        Review::factory()->create(['product_id' => $this->product->id, 'status' => 'hidden']);
        $this->getJson("/api/products/{$this->product->id}/reviews")->assertStatus(200)->assertJsonCount(0, 'data');
    }

    public function test_public_reviews_are_paginated_sorted_and_hide_sensitive_user_fields()
    {
        Review::factory()->create([
            'product_id' => $this->product->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
            'rating' => 4,
            'comment' => 'Old review',
            'created_at' => Carbon::now()->subDays(2),
        ]);
        Review::factory()->create([
            'product_id' => $this->product->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
            'rating' => 5,
            'comment' => 'Newest review',
            'created_at' => Carbon::now(),
        ]);
        Review::factory()->create([
            'product_id' => $this->product->id,
            'user_id' => $this->user->id,
            'status' => 'approved',
            'rating' => 3,
            'comment' => 'Paged review',
            'created_at' => Carbon::now()->subDay(),
        ]);
        Review::factory()->create([
            'product_id' => $this->product->id,
            'user_id' => $this->user->id,
            'status' => 'hidden',
            'rating' => 1,
            'comment' => 'Hidden review',
            'created_at' => Carbon::now()->addMinute(),
        ]);

        $res = $this->getJson("/api/products/{$this->product->id}/reviews?per_page=2")
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');

        $this->assertSame('Newest review', $res->json('data.0.comment'));
        $this->assertSame('Paged review', $res->json('data.1.comment'));
        $this->assertNotNull($res->json('links.next'));
        $this->assertArrayNotHasKey('order_id', $res->json('data.0'));
        $this->assertArrayHasKey('name', $res->json('data.0.user'));
        $this->assertArrayHasKey('avatar', $res->json('data.0.user'));
        $this->assertArrayNotHasKey('email', $res->json('data.0.user'));
        $this->assertArrayNotHasKey('phone', $res->json('data.0.user'));
    }

    public function test_eligible_user_can_submit_review()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 5, 'order_id' => $this->order->id,
                 'comment' => 'Great product!'
             ])->assertStatus(201);
    }

    public function test_review_updates_product_rating_average()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 4, 'order_id' => $this->order->id,
                 'comment' => 'Good'
             ]);
        $this->assertEquals(4, $this->product->fresh()->avg_rating);
    }

    public function test_user_cannot_submit_review_without_comment()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 3, 'order_id' => $this->order->id
             ])->assertStatus(422);
    }

    public function test_eligible_user_can_submit_review_exactly_1_star()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 1, 'order_id' => $this->order->id, 'comment' => 'test'
             ])->assertStatus(201);
    }

    public function test_eligible_user_can_submit_review_exactly_5_stars()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 5, 'order_id' => $this->order->id, 'comment' => 'test'
             ])->assertStatus(201);
    }

    public function test_user_can_delete_own_review()
    {
        $review = Review::factory()->create(['product_id' => $this->product->id, 'user_id' => $this->user->id]);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->deleteJson("/api/reviews/{$review->id}")
             ->assertStatus(200);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    public function test_deleting_review_updates_product_rating()
    {
        $review = Review::factory()->create(['product_id' => $this->product->id, 'user_id' => $this->user->id, 'rating' => 5, 'order_id' => $this->order->id]);
        $this->product->update(['avg_rating' => 5, 'order_id' => $this->order->id]);
        
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->deleteJson("/api/reviews/{$review->id}");
             
        $this->assertEquals(0, $this->product->fresh()->avg_rating);
    }

    public function test_product_list_is_paginated()
    {
        Product::factory()->count(20)->create(['is_active' => true]);
        $res = $this->getJson('/api/products');
        $this->assertArrayHasKey('meta', $res->json());
    }

    public function test_category_list_endpoint_works()
    {
        $this->getJson('/api/categories')->assertStatus(200)->assertJsonCount(1);
    }

    public function test_product_details_includes_category_info()
    {
        $res = $this->getJson("/api/products/{$this->product->id}");
        $this->assertEquals('Phones', $res->json('category.name'));
    }

    public function test_guest_cannot_submit_review()
    {
        $this->postJson("/api/products/{$this->product->id}/reviews", [
            'rating' => 5, 'order_id' => $this->order->id
        ])->assertStatus(401);
    }

    public function test_guest_cannot_delete_review()
    {
        $review = Review::factory()->create(['product_id' => $this->product->id, 'user_id' => $this->user->id]);
        $this->deleteJson("/api/reviews/{$review->id}")->assertStatus(401);
    }

    // ==========================================
    // 20 TRƯỜNG HỢP HACKER PHÁ HOẠI (NEGATIVE/SECURITY)
    // ==========================================

    public function test_hacker_cannot_submit_review_without_buying()
    {
        $otherUser = User::factory()->create([
            'email' => 'other@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);
        $res = $this->postJson('/api/login', ['email' => 'other@example.com', 'password' => 'password123']);
        $token = $res->json('token');
        
        $this->withHeaders(['Authorization' => "Bearer {$token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 5, 'order_id' => $this->order->id, 'comment' => 'test'
             ])->assertStatus(403)
             ->assertJsonPath('message', 'Bạn cần mua sản phẩm này để có thể đánh giá');
    }

    public function test_user_cannot_submit_review_for_uncompleted_order()
    {
        $pendingOrder = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        $pendingOrder->items()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
            'price_at_purchase' => 10000,
        ]);

        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 5,
                 'order_id' => $pendingOrder->id,
                 'comment' => 'test',
             ])->assertStatus(403)
             ->assertJsonPath('message', 'Bạn cần mua sản phẩm này để có thể đánh giá');
    }

    public function test_hacker_cannot_submit_review_twice()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", ['rating' => 5, 'order_id' => $this->order->id, 'comment' => 'test']);
             
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", ['rating' => 4, 'order_id' => $this->order->id, 'comment' => 'test 2'])
             ->assertStatus(422); // Already reviewed
    }

    public function test_hacker_cannot_delete_review_of_another_user()
    {
        $otherUser = User::factory()->create();
        $review = Review::factory()->create(['product_id' => $this->product->id, 'user_id' => $otherUser->id]);
        
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->deleteJson("/api/reviews/{$review->id}")
             ->assertStatus(403); // Forbidden
    }

    public function test_hacker_cannot_give_0_star_rating()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", ['rating' => 0, 'order_id' => $this->order->id])
             ->assertStatus(422);
    }

    public function test_hacker_cannot_give_6_star_rating()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", ['rating' => 6, 'order_id' => $this->order->id])
             ->assertStatus(422);
    }

    public function test_hacker_cannot_send_string_for_rating()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", ['rating' => 'five', 'order_id' => $this->order->id])
             ->assertStatus(422);
    }

    public function test_hacker_xss_in_review_comment()
    {
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 5, 'order_id' => $this->order->id,
                 'comment' => '<script>alert("XSS")</script><b>Máy tốt</b>'
             ])->assertStatus(201);

        $this->assertSame('Máy tốt', $res->json('review.comment'));
        $this->assertDatabaseHas('reviews', ['comment' => 'Máy tốt']);
        $this->assertDatabaseMissing('reviews', ['comment' => '<script>alert("XSS")</script><b>Máy tốt</b>']);
    }

    public function test_hacker_sql_injection_in_search()
    {
        $this->getJson("/api/products?search=' OR 1=1--")->assertStatus(200); // Eloquent escapes this
    }

    public function test_hacker_sql_injection_in_sort()
    {
        $this->getJson("/api/products?sort=price' UNION SELECT * FROM users--")->assertStatus(200); // Handled by sorting mapping
    }

    public function test_hacker_sql_injection_in_category_filter()
    {
        $this->getJson("/api/products?category=' OR 1=1--")->assertStatus(200); // Will just return empty data safely
    }

    public function test_hacker_mass_assignment_is_visible_on_review()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 5, 'order_id' => $this->order->id, 'comment' => 'test',
                 'status' => 'hidden'
             ]);
        // Mặc định luôn là true khi user tạo
        $this->assertDatabaseHas('reviews', ['rating' => 5, 'order_id' => $this->order->id, 'status' => 'approved']);
    }

    public function test_hacker_mass_assignment_user_id_on_review()
    {
        $otherUser = User::factory()->create();
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 5, 'order_id' => $this->order->id, 'comment' => 'test',
                 'user_id' => $otherUser->id
             ]);
        $this->assertDatabaseHas('reviews', ['user_id' => $this->user->id]); // Force lấy auth()->id()
    }

    public function test_hacker_dos_very_long_comment()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/products/{$this->product->id}/reviews", [
                 'rating' => 5, 'order_id' => $this->order->id,
                 'comment' => str_repeat('a', 5000)
             ])->assertStatus(422); // Max 1000 or so
    }

    public function test_hacker_array_in_search_param()
    {
        $this->getJson("/api/products?search[]=test")->assertStatus(200); // Framework handles array in string context safely usually, or ignores.
    }

    public function test_hacker_sql_injection_in_product_slug()
    {
        $this->getJson("/api/products/1' OR 1=1--")->assertStatus(404);
    }

    public function test_hacker_sql_injection_in_review_delete()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->deleteJson("/api/reviews/1' UNION SELECT * FROM reviews--")
             ->assertStatus(404);
    }

    public function test_hacker_accessing_private_product_fields()
    {
        $res = $this->getJson("/api/products/{$this->product->id}");
        $this->assertArrayNotHasKey('cost_price', $res->json()); // Ensure no sensitive data leak
    }

    public function test_hacker_accessing_private_user_fields_in_reviews()
    {
        Review::factory()->create(['product_id' => $this->product->id, 'status' => 'approved']);
        $res = $this->getJson("/api/products/{$this->product->id}/reviews");
        $this->assertArrayNotHasKey('password', $res->json('data.0.user') ?? []);
    }

    public function test_hacker_too_large_pagination_dos()
    {
        $this->getJson('/api/products?page=99999999999999999')->assertStatus(200); // Should fallback safely
    }

    public function test_hacker_negative_pagination_page()
    {
        $this->getJson('/api/products?page=-1')->assertStatus(200); // Should fallback to page 1
    }
}
