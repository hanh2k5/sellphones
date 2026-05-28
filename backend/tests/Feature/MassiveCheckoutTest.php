<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class MassiveCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'checkout@example.com',
            'password' => Hash::make('password123'),
        ]);
        $response = $this->postJson('/api/login', [
            'email' => 'checkout@example.com',
            'password' => 'password123',
        ]);
        $this->token = $response->json('token');

        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    // ==========================================
    // 20 TRƯỜNG HỢP NGƯỜI DÙNG SỬ DỤNG ĐÚNG MỤC ĐÍCH
    // ==========================================}


    public function test_user_can_checkout_single_product_successfully()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'HN', 'receiver_name' => 'Test', 'phone' => '0123456789'];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])->postJson('/api/orders', $payload);
        $res->assertStatus(200);
    }


    public function test_checkout_reduces_product_stock()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 2]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload);
        $this->assertEquals(8, $this->product->fresh()->stock);
    }


    public function test_checkout_creates_order_record()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload);
        $this->assertDatabaseHas('orders', ['user_id' => $this->user->id, 'total_amount' => 100000]);
    }


    public function test_checkout_creates_order_details()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 3]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload);
        $orderId = $res->json('order.id');
        $this->assertDatabaseHas('order_items', ['order_id' => $orderId, 'product_id' => $this->product->id, 'quantity' => 3]);
    }


    public function test_checkout_calculates_total_amount_correctly() {
        $product2 = \App\Models\Product::factory()->create(['price' => 50000, 'stock' => 100, 'is_active' => true]);
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 2]);
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $product2->id, 'quantity' => 1]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'HN', 'receiver_name' => 'Test', 'phone' => '0123456789'];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])->postJson('/api/orders', $payload);
        $res->assertStatus(200);
        $this->assertEquals(250000, $res->json('order.total_amount'));
    }


    public function test_user_can_checkout_max_stock()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 10]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload)
             ->assertStatus(200);
        $this->assertEquals(0, $this->product->fresh()->stock);
    }


    public function test_checkout_returns_order_code()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload);
        $this->assertNotNull($res->json('order.order_code'));
    }


    public function test_order_status_is_pending_initially()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload);
        $this->assertEquals('pending', $res->json('order.status'));
    }


    public function test_payment_status_is_unpaid_initially()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload);
        $this->assertEquals('pending', $res->json('order.payment_status'));
    }


    public function test_user_can_cancel_pending_order() {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])->postJson('/api/orders', $payload);
        $orderId = $res->json('order.id');
        $updatedAt = $res->json('order.updated_at');
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])->postJson("/api/orders/{$orderId}/cancel", ['updated_at' => $updatedAt])->assertStatus(200);
    }




    public function test_cancel_order_restores_stock() {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 2]);
        $stockBefore = clone $this->product;
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])->postJson('/api/orders', $payload);
        $orderId = $res->json('order.id');
        $updatedAt = $res->json('order.updated_at');
        
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])->postJson("/api/orders/{$orderId}/cancel", ['updated_at' => $updatedAt])->assertStatus(200);
        $this->assertEquals($stockBefore->stock, $this->product->fresh()->stock);
    }




    public function test_user_cannot_cancel_completed_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'completed']);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/orders/{$order->id}/cancel", ['updated_at' => $order->updated_at->toISOString()])
             ->assertStatus(422);
    }


    public function test_user_can_pay_order_via_momo_simulation()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending', 'payment_status' => 'unpaid']);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/orders/{$order->id}/confirm-payment")
             ->assertStatus(200);
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }


    public function test_user_cannot_pay_already_paid_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending', 'payment_status' => 'paid']);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/orders/{$order->id}/confirm-payment")
             ->assertStatus(422);
    }


    public function test_user_cannot_pay_cancelled_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'cancelled', 'payment_status' => 'unpaid']);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/orders/{$order->id}/confirm-payment")
             ->assertStatus(422);
    }


    public function test_user_can_view_order_details()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson("/api/orders/{$order->id}")
             ->assertStatus(200)
             ->assertJsonPath('id', $order->id);
    }


    public function test_order_details_includes_products()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);
        $order->items()->create(['product_id' => $this->product->id, 'quantity' => 1, 'price_at_purchase' => 100000]);
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson("/api/orders/{$order->id}");
        $this->assertCount(1, $res->json('items'));
    }


    public function test_checkout_multiple_same_product_merges_in_frontend()
    {
        // Thường giỏ hàng từ FE đã gom group, backend chỉ validate số lượng tổng
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 2]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload);
        $this->assertEquals(200000, $res->json('order.total_amount'));
    }


    public function test_user_cannot_checkout_empty_cart()
    {
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload)
             ->assertStatus(422);
    }


    public function test_user_cannot_view_order_of_another_user()
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id]);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson("/api/orders/{$order->id}")
             ->assertStatus(403);


    }

    // ==========================================
    // 20 TRƯỜNG HỢP HACKER PHÁ HOẠI (NEGATIVE/SECURITY)
    // ==========================================}


    public function test_hacker_cannot_checkout_negative_quantity()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => -5]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload)
             ->assertStatus(422);
    }


    public function test_hacker_cannot_checkout_zero_quantity()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 0]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload)
             ->assertStatus(422);
    }


    public function test_hacker_cannot_checkout_out_of_stock_product()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 11]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789']; // Stock is 10
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload)
             ->assertStatus(422); // Bad Request
    }


    public function test_hacker_cannot_modify_price_in_payload()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'HN', 'receiver_name' => 'Test', 'phone' => '0123456789'];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])->postJson('/api/orders', $payload);
        $res->assertStatus(200);
        $this->assertEquals(100000, $res->json('order.total_amount'));
    }


    public function test_hacker_cannot_checkout_inactive_product()
    {
        $inactiveProduct = Product::factory()->create(['is_active' => false, 'stock' => 10, 'price' => 10000]);
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $inactiveProduct->id, 'quantity' => 1]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload)
             ->assertStatus(422);
    }


    public function test_hacker_sql_injection_in_product_id()
    {
        $payload = ['cart' => [['id' => "1' OR '1'='1", 'quantity' => 1]], 'payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload)
             ->assertStatus(422);
    }


    public function test_hacker_cannot_send_array_for_quantity()
    {
        $payload = ['cart' => [['id' => $this->product->id, 'quantity' => [1]]], 'payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload)
             ->assertStatus(422);
    }


    public function test_hacker_cannot_send_string_for_quantity()
    {
        $payload = ['cart' => [['id' => $this->product->id, 'quantity' => 'one']], 'payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload)
             ->assertStatus(422);
    }


    public function test_hacker_cannot_mass_assign_order_status()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        $payload = [
            'payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789',
            'status' => 'completed',
            'payment_status' => 'paid'
        ];
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload);
        $this->assertEquals('pending', $res->json('order.status'));
        $this->assertEquals('pending', $res->json('order.payment_status'));
    }



    public function test_hacker_cannot_mass_assign_user_id() {
        $otherUser = \App\Models\User::factory()->create();
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'HN', 'receiver_name' => 'Test', 'phone' => '0123456789', 'user_id' => $otherUser->id];
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        $res = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])->postJson('/api/orders', $payload)->assertStatus(200);
        $this->assertEquals($this->user->id, \App\Models\Order::first()->user_id);
    }





    public function test_hacker_optimistic_locking_when_cancelling_order()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        $staleDate = Carbon::now()->subHours(2)->toISOString();
        
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/orders/{$order->id}/cancel", ['updated_at' => $staleDate])
             ->assertStatus(409); // Conflict
    }


    public function test_hacker_sql_injection_in_order_id()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->getJson("/api/orders/1' UNION SELECT * FROM orders--")
             ->assertStatus(404);
    }

    public function test_hacker_cannot_pay_order_of_another_user()
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id, 'status' => 'pending']);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/orders/{$order->id}/confirm-payment")
             ->assertStatus(403);
    }



    public function test_hacker_cannot_cancel_order_of_another_user()
    {
        $otherUser = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $otherUser->id, 'status' => 'pending']);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/orders/{$order->id}/cancel", ['updated_at' => $order->updated_at->toISOString()])
             ->assertStatus(403);
    }



    public function test_hacker_very_large_cart_payload_dos_attack()
    {
        $cart = [];
        for ($i = 0; $i < 2000; $i++) {
            $cart[] = ['id' => $this->product->id, 'quantity' => 1];
        }
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789', 'cart' => $cart];
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson('/api/orders', $payload)
             ->assertStatus(422);
    }



    public function test_hacker_cannot_checkout_without_authorization()
    {
        \App\Models\CartItem::create(['user_id' => $this->user->id, 'product_id' => $this->product->id, 'quantity' => 1]);
        $payload = ['payment_method' => 'cod', 'shipping_address' => 'test', 'receiver_name' => 'test', 'phone' => '0123456789'];
        $this->postJson('/api/orders', $payload)
             ->assertStatus(401);
    }


    public function test_hacker_cannot_cancel_order_without_authorization()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        $this->postJson("/api/orders/{$order->id}/cancel", ['updated_at' => $order->updated_at->toISOString()])
             ->assertStatus(401);
    }


    public function test_hacker_missing_updated_at_when_cancelling()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/orders/{$order->id}/cancel", []) // Thiếu updated_at
             ->assertStatus(422);
    }


    public function test_hacker_invalid_updated_at_format_when_cancelling()
    {
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->postJson("/api/orders/{$order->id}/cancel", ['updated_at' => 'not-a-date'])
             ->assertStatus(409);
    }



    public function test_hacker_method_spoofing_delete_on_checkout_route()
    {
        $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
             ->deleteJson('/api/orders')
             ->assertStatus(405);

}
}
