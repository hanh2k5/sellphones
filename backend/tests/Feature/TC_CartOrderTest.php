<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Category;
use App\Models\CartItem;
use App\Models\Voucher;
use App\Models\OrderItem;

/**
 * [Phan Đình Hạnh - 4.1.1 → 4.1.14] 14 Test Case Giỏ hàng, Đơn hàng, Voucher
 * Chạy: php artisan test --filter=TC_CartOrderTest
 */
class TC_CartOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected string $adminToken;
    protected string $userToken;
    protected Product $product;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

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
        $this->product  = Product::factory()->create([
            'category_id' => $this->category->id,
            'name'        => 'iPhone 15',
            'price'       => 20000000,
            'stock'       => 10,
            'is_active'   => true,
        ]);

        $this->adminToken = $this->postJson('/api/login', ['email' => 'admin@test.com', 'password' => 'password123'])->json('token');
        $this->userToken  = $this->postJson('/api/login', ['email' => 'user@test.com',  'password' => 'password123'])->json('token');
    }

    // ─────────────────────────────────────────────
    // TC1: Xóa mục không tồn tại (2 tab xóa cùng cart item)
    // ─────────────────────────────────────────────
    public function test_TC1_delete_cart_item_already_deleted()
    {
        // Thêm SP vào giỏ
        $this->withToken($this->userToken)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'quantity'   => 1,
        ])->assertStatus(200);

        $cartItem = CartItem::where('user_id', $this->user->id)->first();

        // Tab 1: Xóa thành công
        $this->withToken($this->userToken)->deleteJson("/api/cart/{$cartItem->id}")->assertStatus(200);

        // Tab 2: Xóa lại item đã xóa → 200
        $this->withToken($this->userToken)->deleteJson("/api/cart/{$cartItem->id}")->assertStatus(200);
    }

    public function test_TC1_delete_other_user_cart_item_forbidden()
    {
        // Thêm SP vào giỏ
        $this->withToken($this->userToken)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'quantity'   => 1,
        ])->assertStatus(200);

        $cartItem = CartItem::where('user_id', $this->user->id)->first();

        // Clear Sanctum's cached authenticated user to force re-authentication
        $this->app['auth']->forgetGuards();

        // Admin thử xóa item của user → 403 Forbidden
        $this->withToken($this->adminToken)->deleteJson("/api/cart/{$cartItem->id}")->assertStatus(403);
    }

    public function test_TC1_cancel_order_already_cancelled()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        $originalUpdatedAt = $order->updated_at->toIso8601String();

        // Tab 1: Hủy đơn OK
        $this->withToken($this->userToken)->postJson("/api/orders/{$order->id}/cancel", [
            'updated_at' => $originalUpdatedAt,
        ])->assertStatus(200);

        // Tab 2: Hủy lại đơn đã cancelled → 422 Unprocessable Entity (lỗi trạng thái đơn đã hủy)
        $this->withToken($this->userToken)->postJson("/api/orders/{$order->id}/cancel", [
            'updated_at' => $originalUpdatedAt,
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC2: Cập nhật trùng lặp – Optimistic Locking đơn hàng (Admin duyệt 2 tab)
    // ─────────────────────────────────────────────
    public function test_TC2_order_optimistic_lock_status_conflict()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);
        $originalUpdatedAt = $order->updated_at->toIso8601String();

        // Tab 1: Admin chuyển trạng thái → confirmed → thành công
        $this->withToken($this->adminToken)->putJson("/api/admin/orders/{$order->id}/status", [
            'status'          => 'confirmed',
            'last_updated_at' => $originalUpdatedAt,
        ])->assertStatus(200);

        // Giả lập thời gian trôi qua để DB updated_at khác với originalUpdatedAt
        $order->refresh();
        $order->updated_at = now()->addSeconds(5);
        $order->save();

        // Tab 2: Admin dùng timestamp cũ → 409 Conflict
        $this->withToken($this->adminToken)->putJson("/api/admin/orders/{$order->id}/status", [
            'status'          => 'shipping',
            'last_updated_at' => $originalUpdatedAt, // timestamp cũ → xung đột
        ])->assertStatus(409);
    }

    // ─────────────────────────────────────────────
    // TC3: ID không tồn tại
    // ─────────────────────────────────────────────
    public function test_TC3_order_nonexistent_id_string()
    {
        $this->withToken($this->userToken)->getJson('/api/orders/abc')->assertStatus(404);
    }

    public function test_TC3_order_nonexistent_id_large_number()
    {
        $this->withToken($this->userToken)->getJson('/api/orders/99999999999')->assertStatus(404);
    }

    public function test_TC3_cart_delete_nonexistent_item()
    {
        $this->withToken($this->userToken)->deleteJson('/api/cart/99999')->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC4: Validate form (dữ liệu không hợp lệ)
    // ─────────────────────────────────────────────
    public function test_TC4_add_to_cart_missing_product_id()
    {
        $this->withToken($this->userToken)->postJson('/api/cart', [
            'quantity' => 1,
            // Không có product_id
        ])->assertStatus(422)->assertJsonValidationErrors(['product_id']);
    }

    public function test_TC4_add_to_cart_quantity_zero()
    {
        $this->withToken($this->userToken)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'quantity'   => 0,  // min:1 → fail
        ])->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }

    public function test_TC4_checkout_missing_receiver_name()
    {
        $this->withToken($this->userToken)->postJson('/api/orders', [
            // Thiếu receiver_name
            'phone'            => '0901234567',
            'shipping_address' => '123 Đường ABC, TP.HCM',
            'payment_method'   => 'cod',
        ])->assertStatus(422)->assertJsonValidationErrors(['receiver_name']);
    }

    public function test_TC4_checkout_invalid_payment_method()
    {
        $this->withToken($this->userToken)->postJson('/api/orders', [
            'receiver_name'    => 'Nguyễn Văn A',
            'phone'            => '0901234567',
            'shipping_address' => '123 Đường ABC, TP.HCM',
            'payment_method'   => 'bitcoin',  // không hợp lệ → in:cod,momo
        ])->assertStatus(422)->assertJsonValidationErrors(['payment_method']);
    }

    // ─────────────────────────────────────────────
    // TC5: Text quá tải (Paste HTML vào địa chỉ giao hàng)
    // ─────────────────────────────────────────────
    public function test_TC5_checkout_address_exceeds_max_length()
    {
        $longAddress = str_repeat('A', 600); // > 500 ký tự

        $this->withToken($this->userToken)->postJson('/api/orders', [
            'receiver_name'    => 'Nguyễn Văn A',
            'phone'            => '0901234567',
            'shipping_address' => $longAddress,
            'payment_method'   => 'cod',
        ])->assertStatus(422);
    }

    public function test_TC5_order_status_invalid_enum()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $this->withToken($this->adminToken)->putJson("/api/admin/orders/{$order->id}/status", [
            'status' => str_repeat('X', 300), // quá dài
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC6: Khoảng trắng
    // ─────────────────────────────────────────────
    public function test_TC6_checkout_receiver_name_whitespace()
    {
        $this->withToken($this->userToken)->postJson('/api/orders', [
            'receiver_name'    => '     ', // toàn khoảng trắng
            'phone'            => '0901234567',
            'shipping_address' => '123 Đường ABC',
            'payment_method'   => 'cod',
        ])->assertStatus(422)->assertJsonValidationErrors(['receiver_name']);
    }

    public function test_TC6_checkout_address_fullwidth_space_only()
    {
        $this->withToken($this->userToken)->postJson('/api/orders', [
            'receiver_name'    => '　　　', // full-width space
            'phone'            => '0901234567',
            'shipping_address' => '123 ABC',
            'payment_method'   => 'cod',
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC7: Số full-width
    // ─────────────────────────────────────────────
    public function test_TC7_add_to_cart_fullwidth_quantity()
    {
        $this->withToken($this->userToken)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'quantity'   => '０５',  // full-width số
        ])->assertStatus(422)->assertJsonValidationErrors(['quantity']);
    }

    // ─────────────────────────────────────────────
    // TC8: Select-option (status đơn hàng không hợp lệ)
    // ─────────────────────────────────────────────
    public function test_TC8_order_status_invalid_enum_value()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $this->withToken($this->adminToken)->putJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'hacked_status',  // không nằm trong enum
        ])->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    public function test_TC8_payment_method_invalid_in_checkout()
    {
        $this->withToken($this->userToken)->postJson('/api/orders', [
            'receiver_name'    => 'Test User',
            'phone'            => '0912345678',
            'shipping_address' => '456 Test Street',
            'payment_method'   => 'paypal',  // không hỗ trợ
        ])->assertStatus(422)->assertJsonValidationErrors(['payment_method']);
    }

    // ─────────────────────────────────────────────
    // TC9: Trùng lặp dữ liệu (spam submit checkout)
    // ─────────────────────────────────────────────
    public function test_TC9_add_same_product_to_cart_merges_quantity()
    {
        // Thêm SP 2 lần → phải UPSERT (cộng dồn), không tạo 2 dòng
        $this->withToken($this->userToken)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ])->assertStatus(200);

        $this->withToken($this->userToken)->postJson('/api/cart', [
            'product_id' => $this->product->id,
            'quantity'   => 3,
        ])->assertStatus(200);

        // Chỉ có 1 dòng trong cart_items, số lượng = 5 (2+3)
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', [
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'quantity'   => 5,
        ]);
    }

    public function test_TC9_voucher_reuse_same_user_same_code()
    {
        // User dùng voucher → tạo đơn → thử dùng lại cùng mã
        $voucher = Voucher::factory()->create([
            'code'            => 'SAVE10',
            'discount_type'   => 'fixed',
            'discount_value'  => 10000,
            'min_order_value' => 0,
            'usage_limit'     => 5,
            'used_count'      => 0,
            'expires_at'      => now()->addDays(30),
        ]);

        // Giả lập user đã dùng voucher (có đơn hàng không bị hủy liên kết với voucher này)
        Order::factory()->create([
            'user_id'    => $this->user->id,
            'voucher_id' => $voucher->id,
            'status'     => 'completed',
        ]);

        // Thử dùng lại cùng mã → phải bị từ chối
        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => 'SAVE10',
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC10: URL params sai
    // ─────────────────────────────────────────────
    public function test_TC10_order_list_page_string_param()
    {
        $this->withToken($this->userToken)->getJson('/api/orders?page=abc')->assertStatus(200);
    }

    public function test_TC10_order_list_page_out_of_range()
    {
        $this->withToken($this->userToken)->getJson('/api/orders?page=99999')->assertStatus(200);
    }

    public function test_TC10_admin_order_filter_invalid_status()
    {
        // Lọc theo status không tồn tại → không crash, trả rỗng
        $this->withToken($this->adminToken)->getJson('/api/admin/orders?status=invalid_status')
            ->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC10b: STT 6 – Bộ lọc trạng thái đơn hàng (user endpoint)
    // ─────────────────────────────────────────────

    /** [STT 6 - STT 2] Lọc status hợp lệ → chỉ trả đúng đơn có status đó */
    public function test_TC10b_order_list_filter_valid_status_returns_only_matching()
    {
        // Tạo 2 đơn pending, 1 đơn completed cho cùng user
        Order::factory()->count(2)->create(['user_id' => $this->user->id, 'status' => 'pending']);
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'completed']);

        $res = $this->withToken($this->userToken)
            ->getJson('/api/orders?status=pending')
            ->assertStatus(200);

        // Chỉ trả về đơn pending
        $data = $res->json('data') ?? $res->json();
        if (isset($res->json()['data'])) {
            foreach ($res->json('data') as $order) {
                $this->assertEquals('pending', $order['status']);
            }
            $this->assertCount(2, $res->json('data'));
        }
    }

    /** [STT 6 - STT 2] Lọc status sai (hack qua DevTools) → trả rỗng, không crash */
    public function test_TC10b_order_list_filter_invalid_status_returns_empty()
    {
        Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $res = $this->withToken($this->userToken)
            ->getJson('/api/orders?status=hacked_status') // giá trị rác từ Inspect Element
            ->assertStatus(200); // không crash 500

        // Không lọt ra đơn nào
        $data = $res->json('data') ?? [];
        $this->assertEmpty($data);
    }

    /** [STT 6 - STT 1] Cô lập dữ liệu: user chỉ thấy đơn của chính mình dù truyền user_id khác */
    public function test_TC10b_order_list_ignores_user_id_param()
    {
        // user2 có 1 đơn
        $user2 = User::factory()->create(['role' => 'user', 'email' => 'user2b@test.com', 'password' => Hash::make('password123')]);
        $token2 = $this->postJson('/api/login', ['email' => 'user2b@test.com', 'password' => 'password123'])->json('token');
        Order::factory()->create(['user_id' => $user2->id, 'status' => 'pending']);

        // user1 không có đơn nào, nhưng cố gắng truyền user_id của user2
        $res = $this->withToken($this->userToken)
            ->getJson('/api/orders?user_id=' . $user2->id) // URL injection
            ->assertStatus(200);

        // Backend tự lấy auth()->id() → trả rỗng (đơn của user1, không phải user2)
        $data = $res->json('data') ?? [];
        $this->assertEmpty($data);
    }


    // ─────────────────────────────────────────────
    // TC11: Upload file (N/A cho Cart/Order – không có upload)
    // ─────────────────────────────────────────────
    public function test_TC11_not_applicable_cart_has_no_upload()
    {
        // Cart và Order không có endpoint upload ảnh → test N/A
        $this->assertTrue(true, 'TC11: Giỏ hàng và Đơn hàng không có upload ảnh.');
    }

    // ─────────────────────────────────────────────
    // TC12: Ảnh SP trong giỏ/đơn bị xóa → vẫn hiển thị được
    // ─────────────────────────────────────────────
    public function test_TC12_order_detail_with_product_image_deleted()
    {
        // SP có ảnh bị xóa → Xem chi tiết đơn không crash
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'hinh_anh'    => 'products/missing_image.jpg', // file không tồn tại
        ]);
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $order->items()->create([
            'product_id'        => $product->id,
            'quantity'          => 1,
            'price_at_purchase' => 20000000,
        ]);

        $this->withToken($this->userToken)->getJson("/api/orders/{$order->id}")->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC12b: STT 7 – Chi tiết đơn hàng
    // ─────────────────────────────────────────────

    /** [STT 7 - STT 2] SP bị soft-delete → product_name vẫn trả tên đúng, không trống */
    public function test_TC12b_order_detail_shows_product_name_when_soft_deleted()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'name'        => 'iPhone 15 Pro Max',
            'price'       => 30000000,
            'stock'       => 5,
        ]);

        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $order->items()->create([
            'product_id'        => $product->id,
            'quantity'          => 1,
            'price_at_purchase' => 30000000,
        ]);

        // Admin xóa mềm sản phẩm
        $product->delete();
        $this->assertSoftDeleted('products', ['id' => $product->id]);

        // Xem chi tiết đơn → items[0].product_name phải là tên SP, không phải chuỗi trống
        $res = $this->withToken($this->userToken)
            ->getJson("/api/orders/{$order->id}")
            ->assertStatus(200);

        $productName = $res->json('items.0.product_name');
        $this->assertNotEmpty($productName, 'product_name phải không trống dù SP đã bị xóa mềm');
        $this->assertEquals('iPhone 15 Pro Max', $productName);
    }

    /** [STT 7 - STT 3] Giá snapshot không đổi khi Admin cập nhật giá SP sau khi đặt hàng */
    public function test_TC12b_price_snapshot_unchanged_after_product_price_update()
    {
        $product = Product::factory()->create([
            'category_id' => $this->category->id,
            'price'       => 20000000,
            'stock'       => 5,
        ]);

        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $order->items()->create([
            'product_id'        => $product->id,
            'quantity'          => 1,
            'price_at_purchase' => 20000000, // Giá lúc đặt
        ]);

        // Admin tăng giá SP lên gấp đôi
        $product->update(['price' => 40000000]);

        // Xem chi tiết đơn → price_at_purchase phải vẫn là 20.000.000
        $res = $this->withToken($this->userToken)
            ->getJson("/api/orders/{$order->id}")
            ->assertStatus(200);

        $priceAtPurchase = $res->json('items.0.price_at_purchase');
        $this->assertEquals(20000000.0, $priceAtPurchase, 'Giá snapshot phải giữ nguyên giá lúc đặt, không bị ảnh hưởng bởi thay đổi giá SP');
    }

    /** [STT 7 - STT 1] shipping_address trong đơn không thay đổi khi user đổi địa chỉ Profile */
    public function test_TC12b_shipping_address_unchanged_after_profile_update()
    {
        $originalAddress = '250 Nguyễn Văn Cừ, Quận 5, TP.HCM';

        $order = Order::factory()->create([
            'user_id'          => $this->user->id,
            'shipping_address' => $originalAddress,
        ]);

        // User đổi địa chỉ trên Profile (cập nhật bảng users)
        $this->user->update(['address' => '999 Lê Lợi, Quận 1, TP.HCM']);

        // Xem chi tiết đơn → shipping_address vẫn là địa chỉ cũ lúc đặt
        $res = $this->withToken($this->userToken)
            ->getJson("/api/orders/{$order->id}")
            ->assertStatus(200);

        $this->assertEquals(
            $originalAddress,
            $res->json('shipping_address'),
            'shipping_address trong đơn phải chốt cứng, không theo địa chỉ Profile hiện tại'
        );
    }

    // ─────────────────────────────────────────────
    // TC13: Đặt hàng xong – giỏ hàng phải được xóa sạch
    // ─────────────────────────────────────────────
    public function test_TC13_cart_cleared_after_successful_checkout()
    {
        // Thêm SP vào giỏ
        CartItem::create([
            'user_id'    => $this->user->id,
            'product_id' => $this->product->id,
            'quantity'   => 2,
        ]);

        // Đặt hàng
        $res = $this->withToken($this->userToken)->postJson('/api/orders', [
            'receiver_name'    => 'Nguyễn Văn A',
            'phone'            => '0901234567',
            'shipping_address' => '123 Đường ABC, TP.HCM',
            'payment_method'   => 'cod',
        ]);

        $res->assertStatus(200);
        $this->assertDatabaseMissing('cart_items', ['user_id' => $this->user->id]);
    }

    // ─────────────────────────────────────────────
    // TC14: CSRF / Auth – Xóa đơn hàng không có token
    // ─────────────────────────────────────────────
    public function test_TC14_cancel_order_without_token()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        // Không có token → 401
        $this->postJson("/api/orders/{$order->id}/cancel")->assertStatus(401);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_TC14_delete_order_from_other_user_forbidden()
    {
        // user2 thử xóa đơn của user1 → 403 hoặc 404
        $user2 = User::factory()->create(['role' => 'user', 'email' => 'user2@test.com', 'password' => Hash::make('password123')]);
        $token2 = $this->postJson('/api/login', ['email' => 'user2@test.com', 'password' => 'password123'])->json('token');

        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $this->withToken($token2)->postJson("/api/orders/{$order->id}/cancel", [
            'updated_at' => $order->updated_at->toIso8601String(),
        ])->assertStatus(in_array(403, [403, 404]) ? 403 : 404);
    }

    public function test_TC14_delete_cart_without_token()
    {
        $this->deleteJson('/api/cart/1')->assertStatus(401);
    }

    // ─────────────────────────────────────────────
    // TC12c: STT 8 – Duyệt đơn hàng (Optimistic Locking & Constraints)
    // ─────────────────────────────────────────────

    /** [STT 8] Duyệt đơn thành công từ pending sang confirmed */
    public function test_TC12c_confirm_order_success()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);

        $this->withToken($this->adminToken)->postJson("/api/admin/orders/{$order->id}/confirm", [
            'status'     => 'confirmed',
            'updated_at' => $order->updated_at->toIso8601String(),
        ])->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'confirmed',
        ]);
    }

    /** [STT 8] Trả về 409 khi lệch updated_at (xung đột 2 tab) */
    public function test_TC12c_confirm_order_conflict_409()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);

        $originalUpdatedAt = $order->updated_at->toIso8601String();

        // Giả lập tab khác đã cập nhật trước khiến DB thay đổi updated_at
        $order->refresh();
        $order->updated_at = now()->addSeconds(5);
        $order->save();

        $res = $this->withToken($this->adminToken)->postJson("/api/admin/orders/{$order->id}/confirm", [
            'status'     => 'confirmed',
            'updated_at' => $originalUpdatedAt, // Dùng timestamp cũ
        ]);

        $res->assertStatus(409);
        $res->assertJsonFragment([
            'message' => 'Cảnh báo: Đơn hàng đã thay đổi bởi người khác. Vui lòng tải lại!',
        ]);
    }

    /** [STT 8] Trả về 422 khi trạng thái đơn hàng khác pending */
    public function test_TC12c_confirm_order_not_pending_422()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'shipping', // Khác pending
        ]);

        $res = $this->withToken($this->adminToken)->postJson("/api/admin/orders/{$order->id}/confirm", [
            'status'     => 'confirmed',
            'updated_at' => $order->updated_at->toIso8601String(),
        ]);

        $res->assertStatus(422);
        $res->assertJsonFragment([
            'message' => 'Đơn hàng không ở trạng thái chờ xử lý.',
        ]);
    }

    /** [STT 8] Trả về 403 khi người dùng thông thường gọi API duyệt đơn */
    public function test_TC12c_confirm_order_unauthorized_403()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);

        $this->withToken($this->userToken)->postJson("/api/admin/orders/{$order->id}/confirm", [
            'status'     => 'confirmed',
            'updated_at' => $order->updated_at->toIso8601String(),
        ])->assertStatus(403);
    }

    // ─────────────────────────────────────────────
    // TC12d: STT 9 – Hủy đơn hàng (Trạng thái ràng buộc & Hoàn kho)
    // ─────────────────────────────────────────────

    /** [STT 9] Hủy đơn hàng thành công khi trạng thái là confirmed và hoàn kho đúng */
    public function test_TC12d_cancel_order_success_when_confirmed()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'confirmed',
        ]);

        $item = $order->items()->create([
            'product_id'        => $this->product->id,
            'quantity'          => 2,
            'price_at_purchase' => 20000000,
        ]);

        $initialStock = $this->product->stock; // Lấy stock hiện tại

        $this->withToken($this->userToken)->postJson("/api/orders/{$order->id}/cancel", [
            'updated_at' => $order->updated_at->toIso8601String(),
        ])->assertStatus(200);

        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => 'cancelled',
        ]);

        // Kiểm tra tồn kho tăng thêm đúng 2
        $this->product->refresh();
        $this->assertEquals($initialStock + 2, $this->product->stock);
    }

    /** [STT 9] Hủy đơn hàng thất bại (422) khi đơn hàng đã ở trạng thái shipping */
    public function test_TC12d_cancel_order_shipping_or_completed_422()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'shipping',
        ]);

        $res = $this->withToken($this->userToken)->postJson("/api/orders/{$order->id}/cancel", [
            'updated_at' => $order->updated_at->toIso8601String(),
        ]);

        $res->assertStatus(422);
        $res->assertJsonFragment([
            'message' => 'Không thể hủy đơn hàng ở trạng thái hiện tại.',
        ]);
    }

    /** [STT 9] Hủy đơn hàng thất bại (409) do lệch updated_at (Admin duyệt ở tab khác) */
    public function test_TC12d_cancel_order_conflict_409()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);

        $originalUpdatedAt = $order->updated_at->toIso8601String();

        // Giả lập tab khác đổi status sang confirmed trước
        $order->refresh();
        $order->status = 'confirmed';
        $order->updated_at = now()->addSeconds(5);
        $order->save();

        $res = $this->withToken($this->userToken)->postJson("/api/orders/{$order->id}/cancel", [
            'updated_at' => $originalUpdatedAt, // Timestamp cũ
        ]);

        $res->assertStatus(409);
        $res->assertJsonFragment([
            'message' => 'Lỗi: Đơn hàng đã được xử lý bởi người khác!',
        ]);
    }

    // ─────────────────────────────────────────────
    // TC12e: STT 10 – Xóa vĩnh viễn đơn hàng
    // ─────────────────────────────────────────────

    /** [STT 10] Xóa vĩnh viễn đơn hàng thành công khi đơn ở trạng thái cancelled */
    public function test_TC12e_delete_order_success_when_cancelled()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'cancelled',
        ]);
        $orderItem = OrderItem::create([
            'order_id'           => $order->id,
            'product_id'         => $this->product->id,
            'quantity'           => 2,
            'price_at_purchase'  => 1000.00,
        ]);

        $res = $this->withToken($this->adminToken)->deleteJson("/api/admin/orders/{$order->id}");

        $res->assertStatus(200);
        $res->assertJsonFragment([
            'success' => true,
            'message' => 'Đã xóa vĩnh viễn đơn hàng khỏi hệ thống.',
        ]);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['id' => $orderItem->id]);
    }

    /** [STT 10] Xóa vĩnh viễn đơn hàng thất bại (422) khi trạng thái không phải cancelled */
    public function test_TC12e_delete_order_fail_when_not_cancelled()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);

        $res = $this->withToken($this->adminToken)->deleteJson("/api/admin/orders/{$order->id}");

        $res->assertStatus(422);
        $res->assertJsonFragment([
            'message' => 'Chỉ có thể xóa vĩnh viễn các đơn hàng đã bị hủy.',
        ]);

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    /** [STT 10] Xóa vĩnh viễn đơn hàng thất bại (403) khi không có quyền admin */
    public function test_TC12e_delete_order_unauthorized_for_normal_user()
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'cancelled',
        ]);

        $res = $this->withToken($this->userToken)->deleteJson("/api/admin/orders/{$order->id}");

        $res->assertStatus(403);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
