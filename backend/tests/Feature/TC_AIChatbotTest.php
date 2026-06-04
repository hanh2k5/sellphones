<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\User;
use App\Models\AIChat;
use App\Models\Product;
use App\Services\AIService;

/**
 * [Phan Đình Hạnh] Kiểm thử tính năng Tư vấn khách hàng bằng AI Chatbot (STT 11)
 * Chạy: php artisan test --filter=TC_AIChatbotTest
 */
class TC_AIChatbotTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $userToken;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo user phục vụ test
        $this->user = User::factory()->create([
            'role'     => 'user',
            'email'    => 'user@test.com',
            'password' => Hash::make('password123'),
            'name'     => 'Khách hàng test',
        ]);

        $this->userToken = $this->postJson('/api/login', [
            'email'    => 'user@test.com',
            'password' => 'password123'
        ])->json('token');
    }

    // ─────────────────────────────────────────────
    // TC14: CSRF / Auth – Đảm bảo bảo mật khi chưa đăng nhập
    // ─────────────────────────────────────────────
    public function test_unauthenticated_users_cannot_access_chat_endpoints()
    {
        // 1. Xem lịch sử -> 401
        $this->getJson('/api/ai/chats')->assertStatus(401);

        // 2. Gửi tin nhắn -> 401
        $this->postJson('/api/ai/chats', ['message' => 'Hello'])->assertStatus(401);

        // 3. Xóa lịch sử -> 401
        $this->deleteJson('/api/ai/chats')->assertStatus(401);
    }

    // ─────────────────────────────────────────────
    // TC10: Kiểm tra tham số URL / phân trang – Lịch sử giới hạn 50 tin
    // ─────────────────────────────────────────────
    public function test_users_can_fetch_chat_history_limit_50()
    {
        // Tạo 60 tin nhắn chat cũ cho user này
        for ($i = 1; $i <= 60; $i++) {
            AIChat::create([
                'user_id' => $this->user->id,
                'role' => $i % 2 === 0 ? 'assistant' : 'user',
                'message_content' => "Tin nhắn số $i",
                'created_at' => now()->subMinutes(65 - $i) // Sắp xếp thời gian tăng dần
            ]);
        }

        // Tạo 5 tin nhắn chat của user khác (không được trả về)
        $otherUser = User::factory()->create();
        AIChat::create([
            'user_id' => $otherUser->id,
            'role' => 'user',
            'message_content' => "Tin nhắn của người khác",
            'created_at' => now()
        ]);

        $response = $this->withToken($this->userToken)
            ->getJson('/api/ai/chats')
            ->assertStatus(200);

        $data = $response->json();

        // 1. Giới hạn đúng 50 tin nhắn cũ nhất (do controller dùng limit(50))
        $this->assertCount(50, $data);

        // 2. Không chứa tin nhắn của người khác
        foreach ($data as $chat) {
            $this->assertEquals($this->user->id, $chat['user_id']);
            $this->assertStringNotContainsString("Tin nhắn của người khác", $chat['message_content']);
        }
    }

    // ─────────────────────────────────────────────
    // TC4 & TC6: Kiểm tra nhập bỏ trống / khoảng trắng
    // ─────────────────────────────────────────────
    public function test_sending_empty_message_returns_validation_error()
    {
        // 1. Bỏ trống
        $this->withToken($this->userToken)
            ->postJson('/api/ai/chats', ['message' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);

        // 2. Chỉ có khoảng trắng
        $this->withToken($this->userToken)
            ->postJson('/api/ai/chats', ['message' => '     '])
            ->assertStatus(422);

        // 3. Thiếu hẳn field message
        $this->withToken($this->userToken)
            ->postJson('/api/ai/chats', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);

        // 4. Tin nhắn quá dài (> 1,000 ký tự)
        $this->withToken($this->userToken)
            ->postJson('/api/ai/chats', ['message' => str_repeat('A', 1001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    // ─────────────────────────────────────────────
    // STT 3: Lưu hội thoại và gọi AI trợ lý tư vấn
    // ─────────────────────────────────────────────
    public function test_sending_valid_message_stores_user_and_assistant_messages()
    {
        // Mock AIService để không gọi thật ra ngoài Google API
        $this->mock(AIService::class, function ($mock) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn([
                    'type' => 'text',
                    'content' => 'Xin chào! Tôi là Ngọc, tôi có thể giúp gì cho bạn?'
                ]);
        });

        $response = $this->withToken($this->userToken)
            ->postJson('/api/ai/chats', ['message' => 'Chào bạn, tư vấn cho tôi iPhone 15'])
            ->assertStatus(200);

        $data = $response->json();

        // Kiểm tra cấu trúc tin nhắn phản hồi của assistant
        $this->assertEquals('assistant', $data['role']);
        $this->assertEquals('Xin chào! Tôi là Ngọc, tôi có thể giúp gì cho bạn?', $data['message_content']);

        // Kiểm tra xem database đã lưu đủ 2 tin nhắn (1 user và 1 assistant)
        $this->assertDatabaseHas('ai_chats', [
            'user_id' => $this->user->id,
            'role' => 'user',
            'message_content' => 'Chào bạn, tư vấn cho tôi iPhone 15'
        ]);

        $this->assertDatabaseHas('ai_chats', [
            'user_id' => $this->user->id,
            'role' => 'assistant',
            'message_content' => 'Xin chào! Tôi là Ngọc, tôi có thể giúp gì cho bạn?'
        ]);
    }

    // ─────────────────────────────────────────────
    // TC1: Xóa lịch sử chat
    // ─────────────────────────────────────────────
    public function test_clear_chat_history_returns_success_even_if_empty()
    {
        // 1. Xóa khi chưa có tin nhắn nào
        $this->withToken($this->userToken)
            ->deleteJson('/api/ai/chats')
            ->assertStatus(200)
            ->assertJson(['message' => 'Lịch sử chat đã được xóa thành công.']);

        // 2. Tạo chat và xóa
        AIChat::create([
            'user_id' => $this->user->id,
            'role' => 'user',
            'message_content' => 'Test message',
            'created_at' => now()
        ]);

        $this->assertDatabaseCount('ai_chats', 1);

        $this->withToken($this->userToken)
            ->deleteJson('/api/ai/chats')
            ->assertStatus(200);

        $this->assertDatabaseCount('ai_chats', 0);
    }

    // ─────────────────────────────────────────────
    // STT 3 (Dọn dẹp): Lệnh chat:cleanup xóa tin nhắn cũ > 30 ngày
    // ─────────────────────────────────────────────
    public function test_chat_cleanup_command_deletes_messages_older_than_30_days()
    {
        // 1. Tin nhắn cũ hơn 30 ngày (sử dụng DB::table để bỏ qua boot static tạo created_at = now())
        DB::table('ai_chats')->insert([
            'user_id' => $this->user->id,
            'role' => 'user',
            'message_content' => 'Tin nhắn siêu cũ 40 ngày trước',
            'created_at' => now()->subDays(40)
        ]);

        // 2. Tin nhắn mới (vừa tạo)
        DB::table('ai_chats')->insert([
            'user_id' => $this->user->id,
            'role' => 'user',
            'message_content' => 'Tin nhắn mới 5 ngày trước',
            'created_at' => now()->subDays(5)
        ]);

        $this->assertDatabaseCount('ai_chats', 2);

        // Chạy Artisan command
        $exitCode = Artisan::call('chat:cleanup');
        $this->assertEquals(0, $exitCode);

        // Đảm bảo tin nhắn cũ (>30 ngày) bị xóa, tin nhắn mới (<30 ngày) vẫn còn
        $this->assertDatabaseCount('ai_chats', 1);
        $this->assertDatabaseMissing('ai_chats', ['message_content' => 'Tin nhắn siêu cũ 40 ngày trước']);
        $this->assertDatabaseHas('ai_chats', ['message_content' => 'Tin nhắn mới 5 ngày trước']);
    }

    // ─────────────────────────────────────────────
    // STT 12: AI Chatbot tự động gọi hàm thêm sản phẩm vào giỏ (Function Calling)
    // ─────────────────────────────────────────────
    public function test_ai_chatbot_function_call_add_to_cart_success()
    {
        // 1. Tạo sản phẩm đang hoạt động có tồn kho
        $product = Product::factory()->create([
            'name' => 'Oppo Find N3 512GB',
            'stock' => 10,
            'price' => 15000000,
            'is_active' => true,
        ]);

        // Mock AIService để trả về function call 'add_to_cart'
        $this->mock(AIService::class, function ($mock) use ($product) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn([
                    'type' => 'function_call',
                    'function' => [
                        'name' => 'add_to_cart',
                        'args' => [
                            'product_id' => $product->id,
                            'quantity' => 2
                        ]
                    ]
                ]);
        });

        // 2. Gửi tin nhắn yêu cầu mua
        $response = $this->withToken($this->userToken)
            ->postJson('/api/ai/chats', ['message' => 'Mua giùm tôi 2 cái Oppo Find N3 512GB'])
            ->assertStatus(200);

        // 3. Kiểm tra response
        $response->assertJson([
            'role' => 'assistant',
            'message_content' => 'Ngọc đã thêm 2 sản phẩm **Oppo Find N3 512GB** vào giỏ hàng cho bạn.',
            'action' => 'cart_updated'
        ]);

        // 4. Kiểm tra Database giỏ hàng đã có sản phẩm đó với số lượng là 2
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $this->user->id,
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }

    public function test_ai_chatbot_function_call_add_to_cart_fails_when_inactive()
    {
        // 1. Tạo sản phẩm ngừng kinh doanh
        $inactiveProduct = Product::factory()->create([
            'name' => 'Samsung S21 Ultra',
            'stock' => 5,
            'price' => 12000000,
            'is_active' => false,
        ]);

        $this->mock(AIService::class, function ($mock) use ($inactiveProduct) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn([
                    'type' => 'function_call',
                    'function' => [
                        'name' => 'add_to_cart',
                        'args' => [
                            'product_id' => $inactiveProduct->id,
                            'quantity' => 1
                        ]
                    ]
                ]);
        });

        $response = $this->withToken($this->userToken)
            ->postJson('/api/ai/chats', ['message' => 'Mua Samsung S21 Ultra'])
            ->assertStatus(200);

        $response->assertJson([
            'role' => 'assistant',
            'message_content' => 'Rất tiếc, sản phẩm hiện tại đã hết hàng hoặc ngừng kinh doanh.',
            'action' => null
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $this->user->id,
            'product_id' => $inactiveProduct->id
        ]);
    }

    public function test_ai_chatbot_function_call_add_to_cart_fails_when_out_of_stock()
    {
        // 1. Tạo sản phẩm hết hàng
        $outOfStockProduct = Product::factory()->create([
            'name' => 'iPhone 13',
            'stock' => 0,
            'price' => 14000000,
            'is_active' => true,
        ]);

        $this->mock(AIService::class, function ($mock) use ($outOfStockProduct) {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn([
                    'type' => 'function_call',
                    'function' => [
                        'name' => 'add_to_cart',
                        'args' => [
                            'product_id' => $outOfStockProduct->id,
                            'quantity' => 1
                        ]
                    ]
                ]);
        });

        $response = $this->withToken($this->userToken)
            ->postJson('/api/ai/chats', ['message' => 'Mua iPhone 13'])
            ->assertStatus(200);

        $response->assertJson([
            'role' => 'assistant',
            'message_content' => 'Rất tiếc, sản phẩm hiện tại đã hết hàng hoặc ngừng kinh doanh.',
            'action' => null
        ]);

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $this->user->id,
            'product_id' => $outOfStockProduct->id
        ]);
    }
}
