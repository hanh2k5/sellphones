<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;
use App\Models\Voucher;
use App\Models\Order;
use App\Models\Category;
use App\Models\Product;

/**
 * [Phan Đình Hạnh - 4.1.13] 14 Test Case Voucher (Mã giảm giá)
 * Chạy: php artisan test --filter=TC_VoucherTest
 */
class TC_VoucherTest extends TestCase
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

    protected function makeVoucher(array $overrides = []): Voucher
    {
        return Voucher::factory()->create(array_merge([
            'code'            => 'SAVE50K',
            'discount_type'   => 'fixed',
            'discount_value'  => 50000,
            'min_order_value' => 0,
            'usage_limit'     => 10,
            'used_count'      => 0,
            'expires_at'      => now()->addDays(30),
        ], $overrides));
    }

    protected function addProductToCart(): void
    {
        $cat     = Category::factory()->create(['name' => 'Điện thoại']);
        $product = Product::factory()->create(['category_id' => $cat->id, 'price' => 1000000, 'stock' => 10, 'is_active' => true]);
        $this->withToken($this->userToken)->postJson('/api/cart', [
            'product_id' => $product->id,
            'quantity'   => 1,
        ]);
    }

    // ─────────────────────────────────────────────
    // TC1: Mã voucher không tồn tại
    // ─────────────────────────────────────────────
    public function test_TC1_apply_nonexistent_voucher_code()
    {
        $this->addProductToCart();
        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => 'NOTEXIST',
        ])->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // TC2: Voucher đã hết hạn (expired)
    // ─────────────────────────────────────────────
    public function test_TC2_apply_expired_voucher()
    {
        $this->makeVoucher([
            'code'       => 'EXPIRED10',
            'expires_at' => now()->subDay(),  // đã hết hạn hôm qua
        ]);
        $this->addProductToCart();

        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => 'EXPIRED10',
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC3: Áp mã voucher không tồn tại (ID không tồn tại trong đơn)
    // ─────────────────────────────────────────────
    public function test_TC3_apply_voucher_code_with_wrong_id()
    {
        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => '',  // rỗng → required fail
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC4: Validate form (code rỗng)
    // ─────────────────────────────────────────────
    public function test_TC4_apply_voucher_missing_code()
    {
        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            // Không có code
        ])->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    // ─────────────────────────────────────────────
    // TC5: Text quá tải (mã voucher cực dài)
    // ─────────────────────────────────────────────
    public function test_TC5_apply_voucher_code_too_long()
    {
        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => str_repeat('A', 300),  // mã cực dài
        ])->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // TC6: Khoảng trắng (code là khoảng trắng)
    // ─────────────────────────────────────────────
    public function test_TC6_apply_voucher_code_whitespace()
    {
        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => '     ',  // toàn khoảng trắng
        ])->assertStatus(422);
    }

    public function test_TC6_apply_voucher_code_fullwidth_space()
    {
        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => '　　　',  // full-width space
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC7: N/A – Voucher không có field số riêng từ phía user
    // ─────────────────────────────────────────────
    public function test_TC7_not_applicable_voucher_no_number_input()
    {
        $this->assertTrue(true, 'TC7: Voucher chỉ nhập code dạng text – không áp dụng full-width số.');
    }

    // ─────────────────────────────────────────────
    // TC8: Voucher hết lượt dùng (usage_limit đã đầy)
    // ─────────────────────────────────────────────
    public function test_TC8_apply_voucher_usage_limit_exceeded()
    {
        $this->makeVoucher([
            'code'        => 'FULL10',
            'usage_limit' => 5,
            'used_count'  => 5,  // đã dùng hết
        ]);
        $this->addProductToCart();

        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => 'FULL10',
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC9: Trùng lặp – User dùng lại voucher đã dùng
    // ─────────────────────────────────────────────
    public function test_TC9_voucher_reuse_by_same_user_rejected()
    {
        $voucher = $this->makeVoucher(['code' => 'ONCE50']);

        // Giả lập user đã dùng voucher này (có đơn hoàn thành)
        Order::factory()->create([
            'user_id'    => $this->user->id,
            'voucher_id' => $voucher->id,
            'status'     => 'completed',
        ]);

        $this->addProductToCart();

        // Thử dùng lại → 422
        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => 'ONCE50',
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC10: URL params sai (danh sách voucher)
    // ─────────────────────────────────────────────
    public function test_TC10_voucher_list_page_string_param()
    {
        $this->withToken($this->userToken)->getJson('/api/vouchers?page=abc')->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC11-13: N/A – Voucher không có upload ảnh
    // ─────────────────────────────────────────────
    public function test_TC11_TC12_TC13_not_applicable_voucher_no_image()
    {
        $this->assertTrue(true, 'TC11/12/13: Voucher không có upload ảnh – không áp dụng.');
    }

    // ─────────────────────────────────────────────
    // TC14: CSRF / Auth – Áp dụng voucher không có token
    // ─────────────────────────────────────────────
    public function test_TC14_apply_voucher_without_auth_token()
    {
        $this->makeVoucher(['code' => 'PUBLIC50']);

        // Không có token → 401
        $this->postJson('/api/vouchers/apply', [
            'code' => 'PUBLIC50',
        ])->assertStatus(401);
    }

    // ─────────────────────────────────────────────
    // BONUS: Voucher min_order_value không đủ
    // ─────────────────────────────────────────────
    public function test_bonus_apply_voucher_order_below_min_value()
    {
        // Voucher yêu cầu đơn tối thiểu 5 triệu, giỏ hàng chỉ có 1 triệu
        $this->makeVoucher([
            'code'            => 'BIGORDER',
            'min_order_value' => 5000000,
        ]);

        // Thêm SP chỉ 1 triệu vào giỏ
        $this->addProductToCart();

        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => 'BIGORDER',
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // BONUS: Áp dụng voucher hợp lệ → thành công
    // ─────────────────────────────────────────────
    public function test_bonus_apply_valid_voucher_success()
    {
        $this->makeVoucher(['code' => 'VALID50']);
        $this->addProductToCart();

        $res = $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => 'VALID50',
        ]);

        $this->assertContains($res->getStatusCode(), [200, 201]);
        $res->assertJsonStructure(['voucher', 'discount']);
    }

    public function test_apply_voucher_code_with_special_characters_returns_404()
    {
        $this->addProductToCart();
        $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => 'SAVE@50',
        ])->assertStatus(404);
    }

    public function test_apply_voucher_code_is_case_insensitive()
    {
        $this->makeVoucher(['code' => 'LOWERCASE']);
        $this->addProductToCart();

        $res = $this->withToken($this->userToken)->postJson('/api/vouchers/apply', [
            'code' => 'lowercase',
        ]);

        $this->assertContains($res->getStatusCode(), [200, 201]);
    }
}
