<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\Models\User;

/**
 * [Nguyễn Duy Khang - 4.2.9] 14 Test Case Hồ sơ cá nhân (Profile)
 * Chạy: php artisan test --filter=TC_ProfileTest
 */
class TC_ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $user2;
    protected string $userToken;
    protected string $user2Token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role'     => 'user',
            'name'     => 'Nguyễn Văn A',
            'email'    => 'user@test.com',
            'phone'    => '0901234567',
            'address'  => '123 Đường ABC',
            'password' => Hash::make('password123'),
        ]);
        $this->user2 = User::factory()->create([
            'role'     => 'user',
            'email'    => 'user2@test.com',
            'password' => Hash::make('password123'),
        ]);

        $this->userToken  = $this->postJson('/api/login', ['email' => 'user@test.com',  'password' => 'password123'])->json('token');
        $this->user2Token = $this->postJson('/api/login', ['email' => 'user2@test.com', 'password' => 'password123'])->json('token');
    }

    // ─────────────────────────────────────────────
    // TC1: N/A – Profile không có delete
    // ─────────────────────────────────────────────
    public function test_TC1_not_applicable_profile_no_delete()
    {
        $this->assertTrue(true, 'TC1: Profile không có chức năng xóa – không áp dụng.');
    }

    // ─────────────────────────────────────────────
    // TC2: Cập nhật trùng lặp – Optimistic Locking (2 tab)
    // ─────────────────────────────────────────────
    public function test_TC2_profile_update_optimistic_lock_conflict()
    {
        $originalUpdatedAt = $this->user->updated_at->toIso8601String();

        // Tab 1: Cập nhật thành công
        $res1 = $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => 'Tab1 Name',
            'email'      => 'user@test.com',
            'updated_at' => $originalUpdatedAt,
        ]);
        $res1->assertStatus(200);

        // Xóa cache user trong Auth guard để buộc Laravel load lại user từ DB ở request tiếp theo
        $this->app['auth']->forgetUser();

        // Giả lập thời gian trôi qua để DB updated_at khác với originalUpdatedAt
        $this->user->refresh();
        $this->user->updated_at = now()->addSeconds(5);
        $this->user->save();

        // Tab 2: Dùng timestamp cũ → 409 Conflict
        $res2 = $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => 'Tab2 Name',
            'email'      => 'user@test.com',
            'updated_at' => $originalUpdatedAt,  // đã lỗi thời
        ]);
        $res2->assertStatus(409);
    }

    // ─────────────────────────────────────────────
    // TC3: N/A – Profile không có URL ID
    // ─────────────────────────────────────────────
    public function test_TC3_profile_no_url_id_used()
    {
        // GET /api/profile → lấy theo token, không dùng ID
        $this->withToken($this->userToken)->getJson('/api/profile')->assertStatus(200);
        $this->assertTrue(true, 'TC3: Profile lấy qua token, không dùng ID trong URL.');
    }

    // ─────────────────────────────────────────────
    // TC4: Validate form
    // ─────────────────────────────────────────────
    public function test_TC4_update_profile_invalid_email()
    {
        $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => 'Nguyễn Văn A',
            'email'      => 'not-an-email',  // email sai định dạng
            'updated_at' => $this->user->updated_at->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_TC4_update_profile_empty_name()
    {
        $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => '',  // bắt buộc
            'email'      => 'user@test.com',
            'updated_at' => $this->user->updated_at->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_TC4_change_password_wrong_current()
    {
        $this->withToken($this->userToken)->putJson('/api/profile/password', [
            'current_password'          => 'WrongPassword!',
            'new_password'              => 'NewSecure123',
            'new_password_confirmation' => 'NewSecure123',
        ])->assertStatus(422);
    }

    public function test_TC4_change_password_confirmation_mismatch()
    {
        $this->withToken($this->userToken)->putJson('/api/profile/password', [
            'current_password'          => 'password123',
            'new_password'              => 'NewPassword123',
            'new_password_confirmation' => 'DifferentPass!',  // không khớp
        ])->assertStatus(422)->assertJsonValidationErrors(['new_password']);
    }

    // ─────────────────────────────────────────────
    // TC5: Text quá tải
    // ─────────────────────────────────────────────
    public function test_TC5_profile_name_exceeds_max_length()
    {
        $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => str_repeat('A', 256),  // > max:255
            'email'      => 'user@test.com',
            'updated_at' => $this->user->updated_at->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_TC5_profile_address_exceeds_max_length()
    {
        $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => 'Nguyễn Văn A',
            'email'      => 'user@test.com',
            'address'    => str_repeat('B', 501),  // > max:500
            'updated_at' => $this->user->updated_at->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['address']);
    }

    // ─────────────────────────────────────────────
    // TC6: Khoảng trắng
    // ─────────────────────────────────────────────
    public function test_TC6_profile_name_whitespace_only()
    {
        $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => '     ',  // toàn khoảng trắng
            'email'      => 'user@test.com',
            'updated_at' => $this->user->updated_at->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_TC6_profile_name_fullwidth_space()
    {
        $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => '　　　',  // full-width space
            'email'      => 'user@test.com',
            'updated_at' => $this->user->updated_at->toIso8601String(),
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────
    // TC7: Số full-width (SĐT)
    // ─────────────────────────────────────────────
    public function test_TC7_profile_phone_fullwidth_number()
    {
        // Theo kịch bản test_cases_full.md: profile số full-width lưu thành công (string) → trả về 200
        $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => 'Nguyễn Văn A',
            'email'      => 'user@test.com',
            'phone'      => '０９０１２３４５６７',  // full-width số
            'updated_at' => $this->user->updated_at->toIso8601String(),
        ])->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC8: N/A – Profile không có select-option
    // ─────────────────────────────────────────────
    public function test_TC8_not_applicable_profile_no_select_option()
    {
        $this->assertTrue(true, 'TC8: Profile không có select-option – không áp dụng.');
    }

    // ─────────────────────────────────────────────
    // TC9: Trùng lặp – Đổi email sang email đã dùng bởi user khác
    // ─────────────────────────────────────────────
    public function test_TC9_profile_email_duplicate_with_other_user()
    {
        // user2@test.com đã thuộc về user2
        $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => 'Nguyễn Văn A',
            'email'      => 'user2@test.com',  // email của user2
            'updated_at' => $this->user->updated_at->toIso8601String(),
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    // ─────────────────────────────────────────────
    // TC10: URL params sai (?tab=invalid)
    // ─────────────────────────────────────────────
    public function test_TC10_profile_invalid_tab_param_handled()
    {
        // Backend API GET /api/profile không bị ảnh hưởng bởi ?tab= (chỉ Frontend xử lý)
        $this->withToken($this->userToken)->getJson('/api/profile?tab=invalid_tab')->assertStatus(200);
    }

    // ─────────────────────────────────────────────
    // TC11-13: N/A – Profile không có upload ảnh
    // ─────────────────────────────────────────────
    public function test_TC11_TC12_TC13_not_applicable_profile_no_image()
    {
        $this->assertTrue(true, 'TC11/12/13: Profile không có upload ảnh – không áp dụng.');
    }

    // ─────────────────────────────────────────────
    // TC14: CSRF / Auth – Cập nhật hồ sơ không có token
    // ─────────────────────────────────────────────
    public function test_TC14_update_profile_without_token()
    {
        $this->putJson('/api/profile', [
            'name'       => 'Hacker',
            'email'      => 'hacker@evil.com',
            'updated_at' => now()->toIso8601String(),
        ])->assertStatus(401);

        // Thông tin user không thay đổi
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Nguyễn Văn A']);
    }

    public function test_TC14_change_password_without_token()
    {
        $this->putJson('/api/profile/password', [
            'current_password'          => 'password123',
            'new_password'              => 'NewPass123!',
            'new_password_confirmation' => 'NewPass123!',
        ])->assertStatus(401);
    }

    // ─────────────────────────────────────────────
    // BONUS: Cập nhật profile thành công
    // ─────────────────────────────────────────────
    public function test_bonus_profile_update_success()
    {
        $res = $this->withToken($this->userToken)->putJson('/api/profile', [
            'name'       => 'Nguyễn Văn B',
            'email'      => 'user@test.com',
            'phone'      => '0987654321',
            'updated_at' => $this->user->updated_at->toIso8601String(),
        ]);

        $res->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Nguyễn Văn B']);
    }

    // ─────────────────────────────────────────────
    // BONUS: Đổi mật khẩu thành công
    // ─────────────────────────────────────────────
    public function test_bonus_change_password_success()
    {
        $res = $this->withToken($this->userToken)->putJson('/api/profile/password', [
            'current_password'          => 'password123',
            'new_password'              => 'NewSecure@123',
            'new_password_confirmation' => 'NewSecure@123',
        ]);

        $res->assertStatus(200);

        // Đăng nhập lại với mật khẩu mới
        $this->postJson('/api/login', [
            'email'    => 'user@test.com',
            'password' => 'NewSecure@123',
        ])->assertStatus(200)->assertJsonStructure(['token']);
    }
}
