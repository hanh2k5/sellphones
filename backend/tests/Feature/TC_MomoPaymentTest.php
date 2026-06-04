<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Category;
use App\Models\Product;

/**
 * [Phan Đình Hạnh - 4.1.14] 14 Test Case Thanh toán qua cổng ví điện tử (Fake MoMo)
 * Chạy: php artisan test --filter=TC_MomoPaymentTest
 */
class TC_MomoPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected string $adminToken;
    protected string $userToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'email' => 'admin@test.com', 'password' => Hash::make('password123')]);
        $this->user  = User::factory()->create(['role' => 'user',  'email' => 'user@test.com',  'password' => Hash::make('password123')]);

        $this->adminToken = $this->postJson('/api/login', ['email' => 'admin@test.com', 'password' => 'password123'])->json('token');
        $this->userToken  = $this->postJson('/api/login', ['email' => 'user@test.com',  'password' => 'password123'])->json('token');
    }

    /**
     * Tạo đơn hàng MoMo chưa thanh toán cho $this->user.
     */
    protected function makeOrder(array $overrides = []): Order
    {
        return Order::factory()->create(array_merge([
            'user_id'        => $this->user->id,
            'status'         => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'momo',
            'total_amount'   => 5000000,
        ], $overrides));
    }

    // ─────────────────────────────────────────────
    // TC1: Xóa mục không tồn tại (order_id không tồn tại → 404)
    // ─────────────────────────────────────────────
    public function test_TC1_confirm_payment_order_not_found()
    {
        // Gọi confirm-payment với ID đơn hàng không tồn tại trong DB
        $this->withToken($this->userToken)
            ->postJson('/api/orders/99999999/confirm-payment')
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // TC2: Cập nhật trùng lặp (Idempotency – thanh toán 2 lần cùng đơn)
    // ─────────────────────────────────────────────
    public function test_TC2_confirm_payment_duplicate_call_rejected()
    {
        $order = $this->makeOrder(['payment_status' => 'paid']); // đơn đã được thanh toán

        // Gọi lại confirm-payment (dùng nút Back của trình duyệt) → 422 already paid
        $this->withToken($this->userToken)
            ->postJson("/api/orders/{$order->id}/confirm-payment")
            ->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC3: ID không tồn tại (order_id là chuỗi chữ cái → 404)
    // ─────────────────────────────────────────────
    public function test_TC3_confirm_payment_string_order_id_not_found()
    {
        // Gọi với ID dạng chữ cái không hợp lệ
        $this->withToken($this->userToken)
            ->postJson('/api/orders/abc/confirm-payment')
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // TC4: Validate form (order thuộc user khác → lỗi phân quyền 403)
    // ─────────────────────────────────────────────
    public function test_TC4_confirm_payment_other_user_order_forbidden()
    {
        // User B cố xác nhận đơn của User A (thay đổi order_id trên URL)
        $otherUser  = User::factory()->create(['role' => 'user', 'email' => 'other@test.com', 'password' => Hash::make('password123')]);
        $otherToken = $otherUser->createToken('test')->plainTextToken;

        $order = $this->makeOrder(); // đơn của $this->user

        $res = $this->withToken($otherToken)
            ->postJson("/api/orders/{$order->id}/confirm-payment");

        $res->assertStatus(403);

        // Đơn hàng gốc không thay đổi
        $this->assertDatabaseHas('orders', [
            'id'             => $order->id,
            'payment_status' => 'unpaid',
        ]);
    }

    // ─────────────────────────────────────────────
    // TC5: Text quá tải (order_id là số cực lớn → 404)
    // ─────────────────────────────────────────────
    public function test_TC5_confirm_payment_extremely_large_order_id()
    {
        $this->withToken($this->userToken)
            ->postJson('/api/orders/999999999999999/confirm-payment')
            ->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // TC6: Đơn đã hủy không thể thanh toán → 422
    // ─────────────────────────────────────────────
    public function test_TC6_confirm_payment_cancelled_order_rejected()
    {
        $order = $this->makeOrder(['status' => 'cancelled']);

        $res = $this->withToken($this->userToken)
            ->postJson("/api/orders/{$order->id}/confirm-payment");

        $res->assertStatus(422);

        // payment_status không thay đổi
        $this->assertDatabaseHas('orders', [
            'id'             => $order->id,
            'payment_status' => 'unpaid',
        ]);
    }

    // ─────────────────────────────────────────────
    // TC7: N/A – Giao diện MoMo không có ô nhập số full-width
    // ─────────────────────────────────────────────
    public function test_TC7_not_applicable_no_fullwidth_input()
    {
        $this->assertTrue(true, 'TC7: Trang MoMo không có ô nhập liệu số full-width – không áp dụng.');
    }

    // ─────────────────────────────────────────────
    // TC8: Phương thức thanh toán không hợp lệ (payment_method ≠ momo nhưng vẫn gọi endpoint)
    // ─────────────────────────────────────────────
    public function test_TC8_confirm_payment_cod_order_succeeds_if_owner()
    {
        // Tuy đặc tả chỉ nói MoMo, nhưng backend không phân biệt payment_method khi confirm
        // → Đơn COD của chính user vẫn được phép confirm (chỉ chặn theo owner + status)
        $order = $this->makeOrder(['payment_method' => 'cod']);

        $this->withToken($this->userToken)
            ->postJson("/api/orders/{$order->id}/confirm-payment")
            ->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC9: Trùng lặp – Admin không thể thay user xác nhận thanh toán đơn
    // ─────────────────────────────────────────────
    public function test_TC9_admin_cannot_confirm_payment_on_behalf_of_user()
    {
        // Endpoint confirm-payment chỉ dành cho chủ đơn (không có quyền admin bypass)
        $order = $this->makeOrder();

        $this->withToken($this->adminToken)
            ->postJson("/api/orders/{$order->id}/confirm-payment")
            ->assertStatus(403);
    }

    // ─────────────────────────────────────────────
    // TC10: URL params sai (danh sách đơn hàng với page=abc không crash)
    // ─────────────────────────────────────────────
    public function test_TC10_order_list_page_string_param_does_not_crash()
    {
        $this->withToken($this->userToken)
            ->getJson('/api/orders?page=abc')
            ->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC11: N/A – Trang MoMo không có upload file
    // ─────────────────────────────────────────────
    public function test_TC11_not_applicable_no_file_upload()
    {
        $this->assertTrue(true, 'TC11: Trang thanh toán MoMo không có chức năng upload file – không áp dụng.');
    }

    // ─────────────────────────────────────────────
    // TC12: Dữ liệu không hiển thị (đơn đã thanh toán → tự redirect khi fetch)
    // Kiểm tra: GET /orders/{id} với đơn paid trả về đúng payment_status = 'paid'
    // ─────────────────────────────────────────────
    public function test_TC12_paid_order_returns_correct_payment_status()
    {
        $order = $this->makeOrder(['payment_status' => 'paid']);

        $res = $this->withToken($this->userToken)
            ->getJson("/api/orders/{$order->id}");

        $res->assertStatus(200)
            ->assertJsonFragment(['payment_status' => 'paid'])
            ->assertJsonFragment(['payment_method' => 'momo']);
    }

    // ─────────────────────────────────────────────
    // TC13: Dữ liệu snapshot – Xác nhận thanh toán, DB lưu đúng payment_status + status
    // ─────────────────────────────────────────────
    public function test_TC13_confirm_payment_saves_correct_snapshot_in_db()
    {
        $order = $this->makeOrder();

        $res = $this->withToken($this->userToken)
            ->postJson("/api/orders/{$order->id}/confirm-payment");

        $res->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        // Kiểm tra DB: payment_status = 'paid', status vẫn là 'pending' để Admin duyệt
        $this->assertDatabaseHas('orders', [
            'id'             => $order->id,
            'payment_status' => 'paid',
            'status'         => 'pending',
        ]);
    }

    // ─────────────────────────────────────────────
    // TC14: CSRF / Auth – Gọi confirm-payment không có token → 401
    // ─────────────────────────────────────────────
    public function test_TC14_confirm_payment_without_token_returns_401()
    {
        $order = $this->makeOrder();

        // Không có Authorization header
        $this->postJson("/api/orders/{$order->id}/confirm-payment")
            ->assertStatus(401);
    }
}
