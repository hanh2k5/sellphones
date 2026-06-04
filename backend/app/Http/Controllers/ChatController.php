<?php

namespace App\Http\Controllers;

use App\Services\AIService;
use App\Services\CartService;
use App\Services\ChatService;
use App\Http\Resources\AIChatResource;
use App\Http\Requests\SendChatMessageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * [Phan Đình Hạnh - 4.1.11 & 4.1.12] ChatController
 * Thin Controller: không có AIChat::create/where/delete trực tiếp.
 * ChatService → CRUD lịch sử chat (AIChat model).
 * AIService   → gọi AI model, xử lý prompt.
 * CartService → thêm sản phẩm vào giỏ khi AI gọi function.
 */
class ChatController extends Controller
{
    public function __construct(
        protected AIService   $aiService,
        protected CartService $cartService,
        protected ChatService $chatService,
    ) {}

    /**
     * GET /chat — lấy lịch sử hội thoại của user.
     * ChatService@getHistory xử lý AIChat::where query.
     */
    public function index(Request $request)
    {
        $history = $this->chatService->getHistory($request->user()->id);
        return response()->json(AIChatResource::collection($history)->resolve());
    }

    /**
     * POST /chat — gửi tin nhắn và nhận phản hồi từ AI.
     * ChatService xử lý toàn bộ AIChat CRUD, AIService xử lý AI call.
     */
    public function send(SendChatMessageRequest $request)
    {
        $userId      = $request->user()->id;
        $userMessage = $request->message;

        // 1. Lưu tin nhắn người dùng (ChatService → không query trong Controller)
        $this->chatService->saveMessage($userId, 'user', $userMessage);

        // 2. Lấy lịch sử gần đây để gửi lên AI làm context
        $history = $this->chatService->getRecentHistory($userId);

        // 3. Gọi AI model qua AIService
        $aiResponse = $this->aiService->chat($userMessage, $history);
        Log::info('AI Debug:', (array) $aiResponse);

        if (!$aiResponse || (!isset($aiResponse['type']) && !isset($aiResponse['content']))) {
            $aiResponse = ['type' => 'text', 'content' => 'AI đang bận. Bạn vui lòng thử lại sau.'];
        }

        // 4. Xử lý Function Calling (add_to_cart) [4.1.12]
        if (isset($aiResponse['type']) && $aiResponse['type'] === 'function_call') {
            $func = $aiResponse['function'];
            if ($func['name'] === 'add_to_cart') {
                [$msgText, $action] = $this->handleAddToCart(
                    $userId,
                    (int) $func['args']['product_id'],
                    (int) ($func['args']['quantity'] ?? 1)
                );
                $assistantMsg = $this->chatService->saveMessage($userId, 'assistant', $msgText);
                return response()->json(
                    array_merge((new AIChatResource($assistantMsg))->resolve(), ['action' => $action])
                );
            }
        }

        // 5. AI trả về text bình thường
        $msgText      = $aiResponse['content'] ?? 'Xin lỗi, tôi chưa tìm được câu trả lời phù hợp.';
        $assistantMsg = $this->chatService->saveMessage($userId, 'assistant', $msgText);
        return response()->json((new AIChatResource($assistantMsg))->resolve());
    }

    /**
     * DELETE /chat — xóa toàn bộ lịch sử chat.
     * ChatService@clearHistory xử lý AIChat::where()->delete().
     */
    public function clear(Request $request)
    {
        $this->chatService->clearHistory($request->user()->id);
        return response()->json(['message' => 'Lịch sử chat đã được xóa thành công.']);
    }

    /**
     * Xử lý Function Calling add_to_cart: ủy thác CartService, lấy tên sản phẩm từ kết quả trả về.
     * Mọi validation (stock, is_active) đã được CartService xử lý qua StockException.
     * Controller không query Product model.
     *
     * @return array{0: string, 1: string|null} [message, action]
     */
    private function handleAddToCart(int $userId, int $productId, int $quantity): array
    {
        try {
            $item        = $this->cartService->addToCart($userId, $productId, $quantity);
            $item->load('product');
            $productName = $item->product?->name ?? 'sản phẩm';
            return ["Ngọc đã thêm {$quantity} sản phẩm **{$productName}** vào giỏ hàng cho bạn.", 'cart_updated'];
        } catch (\Exception $e) {
            return ['Rất tiếc, sản phẩm hiện tại đã hết hàng hoặc ngừng kinh doanh.', null];
        }
    }
}
