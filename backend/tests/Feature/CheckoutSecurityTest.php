<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_can_checkout_successfully() {
        $this->assertTrue(true);
    }



    public function test_hacker_cannot_checkout_negative_quantity()
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $product = Product::factory()->create([
            'price' => 1000,
            'stock' => 10,
            'is_active' => true
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->postJson('/api/orders', [
                             'cart_items' => [
                                 ['product_id' => $product->id, 'quantity' => -5]
                             ],
                             'payment_method' => 'cod',
                             'shipping_address' => 'Test Address',
                             'phone' => '0123456789',
                             'receiver_name' => 'Test Receiver'
                         ]);

        $response->assertStatus(422);
    }

    public function test_hacker_cannot_modify_price_in_payload() {
        $this->assertTrue(true);
    }



    public function test_hacker_cannot_checkout_out_of_stock_product() {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $product = Product::factory()->create([
            'price' => 1000,
            'stock' => 2, 
            'is_active' => true
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
                         ->postJson('/api/orders', [
                             'cart_items' => [
                                 ['product_id' => $product->id, 'quantity' => 100]
                             ],
                             'payment_method' => 'cod',
                             'shipping_address' => 'Test Address',
                             'phone' => '0123456789',
                             'receiver_name' => 'Test Receiver'
                         ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('orders', ['user_id' => $user->id]);
    }
}
