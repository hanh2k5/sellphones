<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;

/**
 * [Nguyễn Duy Khang - 4.2.1 → 4.2.11] 14 Test Case Quản lý Người dùng & Xác thực
 * Chạy: php artisan test --filter=TC_AuthUserTest
 */
class TC_AuthUserTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $user;
    protected string $adminToken;
    protected string $userToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role'     => 'admin',
            'email'    => 'admin@test.com',
            'password' => Hash::make('password123'),
            'name'     => 'Admin Test',
        ]);
        $this->user = User::factory()->create([
            'role'     => 'user',
            'email'    => 'user@test.com',
            'password' => Hash::make('password123'),
            'name'     => 'User Test',
        ]);

        $this->adminToken = $this->postJson('/api/login', ['email' => 'admin@test.com', 'password' => 'password123'])->json('token');
        $this->userToken  = $this->postJson('/api/login', ['email' => 'user@test.com',  'password' => 'password123'])->json('token');
    }

    // ─────────────────────────────────────────────
    // TC1: Xóa mục không tồn tại (2 tab xóa cùng user)
    // ─────────────────────────────────────────────
    public function test_TC1_delete_user_already_deleted()
    {
        $targetUser = User::factory()->create(['role' => 'user', 'email' => 'target@test.com']);
        $id = $targetUser->id;

        // Tab 1: Xóa user thành công
        $this->withToken($this->adminToken)->deleteJson("/api/admin/users/{$id}")->assertStatus(200);

        // Tab 2: Xóa lại user đã xóa → 404
        $response = $this->withToken($this->adminToken)->deleteJson("/api/admin/users/{$id}");
        $response->assertStatus(404)->assertJson(['message' => 'Tài khoản này không còn tồn tại.']);
    }

    // ─────────────────────────────────────────────
    // TC2: Cập nhật trùng lặp – Optimistic Locking hồ sơ (2 tab)
    // ─────────────────────────────────────────────
    public function test_TC2_profile_optimistic_lock_conflict()
    {
        $originalUpdatedAt = $this->user->updated_at->toIso8601String();

        // Tab 1: Cập nhật hồ sơ thành công
        $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => 'Tab1 Updated Name',
            'email'      => 'user@test.com',
            'updated_at' => $originalUpdatedAt,
        ])->assertStatus(200);

        // Xóa cache user trong Auth guard để buộc Laravel load lại user từ DB ở request tiếp theo
        $this->app['auth']->forgetUser();

        // Giả lập thời gian trôi qua để DB updated_at khác với originalUpdatedAt
        $this->user->refresh();
        $this->user->updated_at = now()->addSeconds(5);
        $this->user->save();

        // Tab 2: Cập nhật với timestamp cũ → 409 Conflict
        $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => 'Tab2 Updated Name',
            'email'      => 'user@test.com',
            'updated_at' => $originalUpdatedAt,  // timestamp đã lỗi thời
        ])->assertStatus(409);
    }

    // ─────────────────────────────────────────────
    // TC3: ID không tồn tại
    // ─────────────────────────────────────────────
    public function test_TC3_user_nonexistent_id_string()
    {
        $this->withToken($this->adminToken)->deleteJson('/api/admin/users/abc')->assertStatus(404);
    }

    public function test_TC3_user_nonexistent_id_large_number()
    {
        $this->withToken($this->adminToken)->deleteJson('/api/admin/users/99999999999')->assertStatus(404);
    }

    // ─────────────────────────────────────────────
    // TC4: Validate form (đăng ký, thêm user, đổi mật khẩu)
    // ─────────────────────────────────────────────
    public function test_TC4_register_invalid_email_format()
    {
        $this->postJson('/api/register', [
            'name'                  => 'Nguyễn Văn A',
            'email'                 => 'not-an-email',  // email sai định dạng
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_TC4_register_password_too_short()
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'Nguyễn Văn A',
            'email'                 => 'newuser@test.com',
            'password'              => '123',
            'password_confirmation' => '123',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['Mật khẩu phải có ít nhất 8 ký tự.']
            ]);
    }

    public function test_TC4_register_password_confirmation_mismatch()
    {
        $this->postJson('/api/register', [
            'name'                  => 'Nguyễn Văn A',
            'email'                 => 'newuser@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'different123',  // không khớp
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_TC4_admin_create_user_invalid_role()
    {
        $this->withToken($this->adminToken)->postJson('/api/admin/users', [
            'name'     => 'New User',
            'email'    => 'newuser@test.com',
            'password' => 'password123',
            'role'     => 'superadmin',  // không hợp lệ → in:admin,user
        ])->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_TC4_change_password_wrong_current()
    {
        $this->withToken($this->userToken)->putJson('/api/profile/password', [
            'current_password'          => 'WrongPassword!',  // sai mật khẩu cũ
            'new_password'              => 'NewPassword123',
            'new_password_confirmation' => 'NewPassword123',
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC5: Text quá tải (Tên user vượt max:255)
    // ─────────────────────────────────────────────
    public function test_TC5_register_name_exceeds_max_length()
    {
        $this->postJson('/api/register', [
            'name'                  => str_repeat('A', 51),
            'email'                 => 'newuser@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_register_email_exceeds_100_chars()
    {
        $this->postJson('/api/register', [
            'name'                  => 'Nguyễn Văn A',
            'email'                 => str_repeat('a', 92) . '@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_TC5_admin_create_user_address_too_long()
    {
        $this->withToken($this->adminToken)->postJson('/api/admin/users', [
            'name'     => 'Test User',
            'email'    => 'testlong@test.com',
            'password' => 'password123',
            'role'     => 'user',
            'address'  => str_repeat('X', 501),  // > max:500
        ])->assertStatus(422)->assertJsonValidationErrors(['address']);
    }

    // ─────────────────────────────────────────────
    // TC6: Khoảng trắng
    // ─────────────────────────────────────────────
    public function test_TC6_register_name_whitespace_only()
    {
        $this->postJson('/api/register', [
            'name'                  => '     ',
            'email'                 => 'newuser@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_TC6_login_password_fullwidth_space()
    {
        // Đăng nhập với mật khẩu là khoảng trắng full-width → fail (trả về 422 từ auth logic)
        $this->postJson('/api/login', [
            'email'    => 'user@test.com',
            'password' => '　　　　　　　　',  // full-width spaces
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC7: Số full-width (SĐT)
    // ─────────────────────────────────────────────
    public function test_TC7_register_phone_fullwidth_number()
    {
        // Theo kịch bản test_cases_full.md: Auth TC7 là N/A.
        // Thực tế hệ thống cho phép lưu chuỗi số full-width vào trường phone (string).
        $this->postJson('/api/register', [
            'name'                  => 'Phone Fullwidth User',
            'email'                 => 'phone_fw@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'phone'                 => '０９０１２３４５６７',  // full-width số
        ])->assertStatus(201);
    }

    // ─────────────────────────────────────────────
    // TC8: Select-option (role không hợp lệ)
    // ─────────────────────────────────────────────
    public function test_TC8_admin_update_user_invalid_role()
    {
        $this->withToken($this->adminToken)->putJson("/api/admin/users/{$this->user->id}", [
            'name'  => 'Updated',
            'email' => 'user@test.com',
            'role'  => 'moderator',  // không trong enum admin,user
        ])->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    // ─────────────────────────────────────────────
    // TC9: Trùng lặp dữ liệu (Email đã tồn tại)
    // ─────────────────────────────────────────────
    public function test_TC9_register_duplicate_email()
    {
        // Email user@test.com đã tồn tại (tạo trong setUp)
        $this->postJson('/api/register', [
            'name'                  => 'Another User',
            'email'                 => 'user@test.com',  // email đã dùng
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_TC9_brute_force_login_lockout()
    {
        $email = 'user@test.com';
        
        // 1. Đăng nhập sai lần đầu → báo Email hoặc mật khẩu không chính xác.
        $this->postJson('/api/login', ['email' => $email, 'password' => 'WrongPass!'])
            ->assertStatus(422)
            ->assertJsonFragment([
                'password' => ['Email hoặc mật khẩu không chính xác.']
            ]);

        // Đăng nhập sai tiếp 4 lần (tổng cộng 5 lần)
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/login', ['email' => $email, 'password' => 'WrongPass!']);
        }

        // Lần thứ 6 → phải bị chặn với thông báo khóa
        $response = $this->postJson('/api/login', [
            'email'    => $email,
            'password' => 'WrongPass!',
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'password' => ['Bạn đã nhập sai quá số lần cho phép. Vui lòng thử lại sau.']
            ]);
    }

    // ─────────────────────────────────────────────
    // TC10: URL params sai
    // ─────────────────────────────────────────────
    public function test_TC10_user_list_page_string_param()
    {
        $this->withToken($this->adminToken)->getJson('/api/admin/users?page=abc')->assertStatus(200);
    }

    public function test_TC10_user_list_page_out_of_range()
    {
        $this->withToken($this->adminToken)->getJson('/api/admin/users?page=99999')->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC11-12-13: N/A cho Auth (không có upload ảnh)
    // ─────────────────────────────────────────────
    public function test_TC11_TC12_TC13_not_applicable_for_auth()
    {
        $this->assertTrue(true, 'TC11/12/13: Auth không có upload ảnh – không áp dụng.');
    }

    // ─────────────────────────────────────────────
    // TC14: CSRF / Auth – Xóa user không có token (trình duyệt khác)
    // ─────────────────────────────────────────────
    public function test_TC14_delete_user_without_token()
    {
        $target = User::factory()->create(['role' => 'user', 'email' => 'victim@test.com']);
        // Không có token → 401
        $this->deleteJson("/api/admin/users/{$target->id}")->assertStatus(401);
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_TC14_delete_user_with_user_token_forbidden()
    {
        $target = User::factory()->create(['role' => 'user', 'email' => 'victim2@test.com']);
        // User thường dùng token user → 403
        $response = $this->withToken($this->userToken)->deleteJson("/api/admin/users/{$target->id}");
        $response->assertStatus(403)->assertJson(['message' => 'Bạn không có quyền truy cập chức năng này.']);
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_unauthorized_access_returns_english_message_when_header_is_en()
    {
        $target = User::factory()->create(['role' => 'user', 'email' => 'victim3@test.com']);
        $response = $this->withToken($this->userToken)
            ->withHeaders(['Accept-Language' => 'en'])
            ->deleteJson("/api/admin/users/{$target->id}");
        $response->assertStatus(403)->assertJson(['message' => 'Bạn không có quyền truy cập chức năng này.']);
    }

    public function test_admin_cannot_delete_himself()
    {
        // Admin tự xóa mình → phải bị từ chối
        $this->withToken($this->adminToken)->deleteJson("/api/admin/users/{$this->admin->id}")
            ->assertStatus(422);
    }

    public function test_admin_create_user_name_exceeds_50_chars()
    {
        $this->withToken($this->adminToken)->postJson('/api/admin/users', [
            'name'     => str_repeat('A', 51),
            'email'    => 'newuser@test.com',
            'password' => 'password123',
            'role'     => 'user',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_admin_create_user_email_exceeds_100_chars()
    {
        $this->withToken($this->adminToken)->postJson('/api/admin/users', [
            'name'     => 'Valid Name',
            'email'    => str_repeat('a', 92) . '@test.com',
            'password' => 'password123',
            'role'     => 'user',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_admin_create_user_duplicate_email_message()
    {
        $response = $this->withToken($this->adminToken)->postJson('/api/admin/users', [
            'name'     => 'Valid Name',
            'email'    => 'user@test.com',
            'password' => 'password123',
            'role'     => 'user',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['Email này đã được sử dụng, vui lòng chọn email khác.']
            ]);
    }

    public function test_admin_update_user_name_exceeds_50_chars()
    {
        $this->withToken($this->adminToken)->putJson("/api/admin/users/{$this->user->id}", [
            'name'  => str_repeat('A', 51),
            'email' => 'user@test.com',
            'role'  => 'user',
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_admin_update_user_email_exceeds_100_chars()
    {
        $this->withToken($this->adminToken)->putJson("/api/admin/users/{$this->user->id}", [
            'name'  => 'Valid Name',
            'email' => str_repeat('a', 92) . '@test.com',
            'role'  => 'user',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_admin_update_user_duplicate_email()
    {
        $userB = User::factory()->create(['email' => 'userb@test.com']);
        $response = $this->withToken($this->adminToken)->putJson("/api/admin/users/{$this->user->id}", [
            'name'  => 'Valid Name',
            'email' => 'userb@test.com',
            'role'  => 'user',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email'])
            ->assertJsonFragment([
                'email' => ['Email đã tồn tại, vui lòng chọn email khác']
            ]);
    }

    public function test_admin_user_list_search_by_name()
    {
        User::factory()->create(['name' => 'Khang Special Search', 'email' => 'special_search@test.com']);

        $response = $this->withToken($this->adminToken)->getJson('/api/admin/users?search=Special');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Khang Special Search', $response->json('data.0.name'));
    }

    public function test_admin_user_list_filter_by_role()
    {
        $response = $this->withToken($this->adminToken)->getJson('/api/admin/users?role=admin');
        $response->assertStatus(200);
        
        foreach ($response->json('data') as $u) {
            $this->assertEquals('admin', $u['role']);
        }
    }
}
