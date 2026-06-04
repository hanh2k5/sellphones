<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;

class TC_ForgotPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email'    => 'user@test.com',
            'password' => Hash::make('password123'),
            'name'     => 'Test User',
        ]);
    }

    public function test_forgot_password_email_not_found()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@test.com',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Email không tồn tại trong hệ thống.',
            ]);
    }

    public function test_forgot_password_email_found_generates_otp()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'user@test.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.',
            ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'user@test.com',
        ]);
    }

    public function test_reset_password_validation_errors()
    {
        // 1. Mật khẩu ngắn hơn 8 ký tự
        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'user@test.com',
            'otp'                   => '123456',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['Mật khẩu mới phải có ít nhất 8 ký tự.'],
            ]);

        // 2. Xác nhận mật khẩu không khớp
        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'user@test.com',
            'otp'                   => '123456',
            'password'              => 'newpassword123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password'])
            ->assertJsonFragment([
                'password' => ['Mật khẩu xác nhận không khớp.'],
            ]);
    }

    public function test_reset_password_otp_expired()
    {
        // Tạo token đã quá 15 phút
        DB::table('password_reset_tokens')->insert([
            'email'      => 'user@test.com',
            'token'      => Hash::make('123456'),
            'created_at' => Carbon::now()->subMinutes(16),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'user@test.com',
            'otp'                   => '123456',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.',
            ]);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'user@test.com',
        ]);
    }

    public function test_reset_password_otp_incorrect()
    {
        DB::table('password_reset_tokens')->insert([
            'email'      => 'user@test.com',
            'token'      => Hash::make('123456'),
            'created_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'user@test.com',
            'otp'                   => '654321', // OTP sai
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Mã OTP không chính xác.',
            ]);
    }

    public function test_reset_password_success()
    {
        DB::table('password_reset_tokens')->insert([
            'email'      => 'user@test.com',
            'token'      => Hash::make('123456'),
            'created_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email'                 => 'user@test.com',
            'otp'                   => '123456',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.',
            ]);

        // Kiểm tra token đã bị xóa
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'user@test.com',
        ]);

        // Kiểm tra mật khẩu đã được cập nhật
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }
}
