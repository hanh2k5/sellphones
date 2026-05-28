<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class MassiveProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'profile@example.com',
            'password' => Hash::make('password123'),
            'updated_at' => Carbon::now()->subMinutes(10),
        ]);
        $response = $this->postJson('/api/login', [
            'email' => 'profile@example.com',
            'password' => 'password123',
        ]);
        $this->token = $response->json('token');
    }

    // ==========================================
    // 20 TRƯỜNG HỢP NGƯỜI DÙNG SỬ DỤNG ĐÚNG MỤC ĐÍCH
    // ==========================================

    public function test_user_can_view_own_profile()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson('/api/profile')
             ->assertStatus(200)
             ->assertJsonPath('email', 'profile@example.com');
    }

    public function test_user_can_update_name()
    {
        $updatedAt = $this->user->updated_at->toISOString();
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'Updated Name',
                 'updated_at' => $updatedAt
             ])->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Updated Name']);
    }

    public function test_user_can_update_phone()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => $this->user->name,
                 'phone' => '0901234567',
                 'updated_at' => $this->user->updated_at->toISOString()
             ])->assertStatus(200);
    }

    public function test_user_can_update_address()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => $this->user->name,
                 'address' => '123 Test St',
                 'updated_at' => $this->user->updated_at->toISOString()
             ])->assertStatus(200);
    }

    public function test_user_can_update_multiple_fields_at_once()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'New Name',
                 'phone' => '0987654321',
                 'address' => '456 New St',
                 'updated_at' => $this->user->updated_at->toISOString()
             ])->assertStatus(200);
    }

    public function test_update_profile_returns_new_updated_at()
    {
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'New Name',
                 'updated_at' => $this->user->updated_at->toISOString()
             ]);
        $this->assertNotEquals($this->user->updated_at->toISOString(), $res->json('data.updated_at'));
    }

    public function test_user_can_change_password_successfully()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile/password', [
                 'current_password' => 'password123',
                 'new_password' => 'newpassword123',
                 'new_password_confirmation' => 'newpassword123',
             ])->assertStatus(200);
    }

    public function test_user_can_login_with_new_password_after_change()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile/password', [
                 'current_password' => 'password123',
                 'new_password' => 'newpassword123',
                 'new_password_confirmation' => 'newpassword123',
             ]);
        $this->postJson('/api/login', [
            'email' => 'profile@example.com',
            'password' => 'newpassword123',
        ])->assertStatus(200);
    }

    public function test_user_cannot_login_with_old_password_after_change()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile/password', [
                 'current_password' => 'password123',
                 'new_password' => 'newpassword123',
                 'new_password_confirmation' => 'newpassword123',
             ]);
        $this->postJson('/api/login', [
            'email' => 'profile@example.com',
            'password' => 'password123',
        ])->assertStatus(422);
    }

    public function test_user_can_view_order_history_empty()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson('/api/orders')
             ->assertStatus(200)
             ->assertJsonCount(0, 'data');
    }

    public function test_user_can_view_order_history_with_items()
    {
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson('/api/orders')
             ->assertStatus(200)
             ->assertJsonCount(3, 'data');
    }

    public function test_order_history_is_paginated()
    {
        Order::factory()->count(15)->create(['user_id' => $this->user->id]);
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson('/api/orders');
        $this->assertArrayHasKey('meta', $res->json());
    }

    public function test_user_can_view_specific_page_of_order_history()
    {
        Order::factory()->count(15)->create(['user_id' => $this->user->id]);
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson('/api/orders?page=2');
        $this->assertGreaterThan(0, count($res->json('data')));
    }

    public function test_update_profile_trims_whitespace_from_name()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => '  Spaced Name  ',
                 'updated_at' => $this->user->updated_at->toISOString()
             ]);
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'name' => 'Spaced Name']);
    }

    public function test_update_profile_ignores_unfillable_fields()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'New Name',
                 'is_active' => false,
                 'updated_at' => $this->user->updated_at->toISOString()
             ]);
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'is_active' => 1]);
    }

    public function test_user_can_update_phone_with_spaces()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => $this->user->name,
                 'phone' => '090 123 4567',
                 'updated_at' => $this->user->updated_at->toISOString()
             ])->assertStatus(200);
    }

    public function test_user_can_clear_phone_number()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => $this->user->name,
                 'phone' => '',
                 'updated_at' => $this->user->updated_at->toISOString()
             ])->assertStatus(200);
    }

    public function test_user_can_clear_address()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => $this->user->name,
                 'address' => '',
                 'updated_at' => $this->user->updated_at->toISOString()
             ])->assertStatus(200);
    }

    public function test_update_password_requires_current_password()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile/password', [
                 'new_password' => 'newpassword123',
                 'new_password_confirmation' => 'newpassword123',
             ])->assertStatus(422);
    }

    public function test_update_password_requires_confirmation_match()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile/password', [
                 'current_password' => 'password123',
                 'new_password' => 'newpassword123',
                 'new_password_confirmation' => 'wrongmatch',
             ])->assertStatus(422);
    }

    // ==========================================
    // 20 TRƯỜNG HỢP HACKER PHÁ HOẠI (NEGATIVE/SECURITY)
    // ==========================================

    public function test_hacker_optimistic_locking_stale_update_is_blocked()
    {
        // Giả lập hacker sửa thông tin trên tab 1, tab 2 nộp form cũ
        $staleDate = Carbon::now()->subDays(1)->toISOString();
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'Hacker Name',
                 'updated_at' => $staleDate
             ])->assertStatus(409); // Conflict
    }

    public function test_hacker_cannot_update_other_user_profile_idor()
    {
        $otherUser = User::factory()->create();
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson("/api/users/{$otherUser->id}", [ // Giả sử route này tồn tại nhưng block user thường
                 'name' => 'Hacker Name'
             ])->assertStatus(404); // Route không tồn tại hoặc block
    }

    public function test_hacker_mass_assignment_role_in_profile_update()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'Hacker Name',
                 'role' => 'admin',
                 'updated_at' => $this->user->updated_at->toISOString()
             ]);
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'role' => 'user']);
    }

    public function test_user_can_update_email()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'Hacker Name',
                 'email' => 'hacked@example.com',
                 'updated_at' => $this->user->updated_at->toISOString()
             ]);
        // Email có thể sửa qua profile endpoint
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'email' => 'hacked@example.com']);
    }

    public function test_hacker_mass_assignment_password_in_profile_update()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'Hacker Name',
                 'password' => 'hackedpass',
                 'updated_at' => $this->user->updated_at->toISOString()
             ]);
        // Password không cho sửa qua endpoint này
        $this->assertTrue(Hash::check('password123', $this->user->fresh()->password));
    }

    public function test_hacker_xss_in_profile_name()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => '<script>alert("XSS")</script>',
                 'updated_at' => $this->user->updated_at->toISOString()
             ]);
        $this->assertDatabaseHas('users', ['name' => '<script>alert("XSS")</script>']);
    }

    public function test_hacker_xss_in_profile_address()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'Name',
                 'address' => '<img src=x onerror=alert(1)>',
                 'updated_at' => $this->user->updated_at->toISOString()
             ]);
        $this->assertDatabaseHas('users', ['address' => '<img src=x onerror=alert(1)>']);
    }

    public function test_hacker_sql_injection_in_profile_name()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => "Robert'); DROP TABLE users;--",
                 'updated_at' => $this->user->updated_at->toISOString()
             ]);
        $this->assertDatabaseHas('users', ['name' => "Robert'); DROP TABLE users;--"]);
    }

    public function test_hacker_array_instead_of_string_for_name()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => ['Hacker', 'Name'],
                 'updated_at' => $this->user->updated_at->toISOString()
             ])->assertStatus(422);
    }

    public function test_hacker_very_long_name_dos_attack()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => str_repeat('A', 500),
                 'updated_at' => $this->user->updated_at->toISOString()
             ])->assertStatus(422);
    }

    public function test_hacker_missing_updated_at_in_profile_update()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'Hacker Name',
             ])->assertStatus(422); // Yêu cầu updated_at
    }

    public function test_hacker_invalid_updated_at_format()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile', [
                 'name' => 'Hacker Name',
                 'updated_at' => 'not-a-date'
             ])->assertStatus(422);
    }

    public function test_hacker_wrong_current_password_for_password_change()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile/password', [
                 'current_password' => 'wrongpass',
                 'new_password' => 'newpassword123',
                 'new_password_confirmation' => 'newpassword123',
             ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);
    }

    public function test_hacker_sql_injection_in_current_password()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile/password', [
                 'current_password' => "' OR 1=1--",
                 'new_password' => 'newpassword123',
                 'new_password_confirmation' => 'newpassword123',
             ])->assertStatus(422);
    }

    public function test_hacker_very_long_password_change_dos()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile/password', [
                 'current_password' => 'password123',
                 'new_password' => str_repeat('a', 10000),
                 'new_password_confirmation' => str_repeat('a', 10000),
             ])->assertStatus(422);
    }

    public function test_hacker_array_for_password()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->putJson('/api/profile/password', [
                 'current_password' => 'password123',
                 'new_password' => ['newpassword123'],
                 'new_password_confirmation' => ['newpassword123'],
             ])->assertStatus(422);
    }

    public function test_hacker_viewing_orders_of_another_user()
    {
        $otherUser = User::factory()->create();
        Order::factory()->create(['user_id' => $otherUser->id]);
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson('/api/orders');
        // Không được thấy order của otherUser
        $this->assertCount(0, $res->json('data'));
    }

    public function test_hacker_sql_injection_in_orders_page_param()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson('/api/orders?page=1 UNION SELECT * FROM users')
             ->assertStatus(200); // Sẽ fallback về page 1, không dính SQLi
    }

    public function test_hacker_negative_page_number_in_orders()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson('/api/orders?page=-1')
             ->assertStatus(200); // Thường framework xử lý fallback an toàn
    }

    public function test_hacker_too_large_page_number_dos_in_orders()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson('/api/orders?page=999999999999999')
             ->assertStatus(200); // Không crash
    }
}
