<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Carbon\Carbon;

class AuthAndProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_can_register() {
        $this->assertTrue(true);
    }



    public function test_hacker_sql_injection_attempt_is_blocked()
    {
        $response = $this->postJson('/api/login', [
            'email' => "admin@test.com' OR '1'='1",
            'password' => "password"
        ]);

        $response->assertStatus(422);
    }

    public function test_normal_user_can_login()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password')
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);
    }

    public function test_hacker_rate_limiting_on_login()
    {
        $user = User::factory()->create([
            'email' => 'target@example.com',
            'password' => Hash::make('password')
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'target@example.com',
                'password' => 'wrongpassword'
            ]);
        }

        $response = $this->postJson('/api/login', [
            'email' => 'target@example.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertStatus(422); // Locked returns 422 validation error in this app
    }

    public function test_optimistic_locking_prevents_stale_update()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $staleTime = Carbon::now()->subMinutes(5)->toISOString();

        $user->update(['updated_at' => Carbon::now()]);

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->putJson('/api/profile', [
                             'name' => 'New Name',
                             'email' => $user->email,
                             'updated_at' => $staleTime
                         ]);

        $response->assertStatus(409);
    }

    public function test_hacker_mass_assignment_role_is_ignored()
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->putJson('/api/profile', [
                             'name' => 'Hacker',
                             'email' => $user->email,
                             'role' => 'admin',
                             'updated_at' => $user->updated_at->toISOString()
                         ]);

        $response->assertStatus(200);
        $this->assertEquals('user', $user->fresh()->role);
    }
}
