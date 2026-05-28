<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/admin/dashboard');

        $response->assertStatus(200);
    }

    public function test_normal_user_is_blocked_from_admin_dashboard()
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->getJson('/api/admin/dashboard');

        // Should be forbidden because they are not admin
        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_is_blocked_from_admin_dashboard()
    {
        $response = $this->getJson('/api/admin/dashboard');

        $response->assertStatus(401);
    }

    public function test_admin_can_soft_delete_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->deleteJson('/api/admin/products/' . $product->id);

        $response->assertStatus(200);

        // Product should still exist in DB but marked as deleted
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_normal_user_cannot_delete_product()
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $product = Product::factory()->create(['is_active' => true]);

        // Hacker tries to call the admin delete endpoint
        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->deleteJson('/api/admin/products/' . $product->id);

        $response->assertStatus(403);
        
        // Product should NOT be deleted
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    public function test_admin_can_restore_product()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::factory()->create(['is_active' => true]);
        $product->delete(); // Soft delete it

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->postJson('/api/admin/products/' . $product->id . '/restore');

        $response->assertStatus(200);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }
}
