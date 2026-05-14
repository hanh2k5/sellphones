<?php

namespace App\Http\Controllers;

use App\Models\AIChat;
use App\Models\Product;
use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * [Phan Đình Hạnh - 4.1.11 & 4.1.12] Quản lý hội thoại Chatbot và Function Calling
 */
class ChatController extends Controller
{
    protected $aiService;
    protected $cartService;

    public function __construct(AIService $aiService, \App\Services\CartService $cartService)
    {
        $this->aiService = $aiService;
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $history = AIChat::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();
        return response()->json($history);
    }

    public function send(Request $request)
    {
        $request->validate(['message' => 'required|string']);
        $userId = $request->user()->id;
        $userMessage = $request->message;

        // 1. Lưu tin nhắn người dùng
        AIChat::create(['user_id' => $userId, 'role' => 'user', 'message_content' => $userMessage]);

        // 2. Lấy lịch sử gần đây
        $history = AIChat::where('user_id', $userId)->orderBy('created_at', 'desc')->limit(10)->get()->reverse()->toArray();

        // 3. Gọi AI
        $aiResponse = $this->aiService->chat($userMessage, $history);
        
        // Ghi log để kiểm tra nếu cần
        Log::info("AI Debug:", (array)$aiResponse);

        // Kiểm tra dữ liệu an toàn
        if (!$aiResponse || (!isset($aiResponse['type']) && !isset($aiResponse['content']))) {
            $aiResponse = ['type' => 'text', 'content' => "AI đang bận, bạn thử lại sau nhé!"];
        }

        // 4. Xử lý kết quả trả về từ AI (Function Calling - 4.1.12)
        if (isset($aiResponse['type']) && $aiResponse['type'] === 'function_call') {
            $func = $aiResponse['function'];
            if ($func['name'] === 'add_to_cart') {
                $productId = $func['args']['product_id'];
                $quantity = $func['args']['quantity'] ?? 1;

                $product = Product::find($productId);
                if (!$product || $product->stock < $quantity) {
                    $msgText = "Rất tiếc, sản phẩm hiện tại không đủ hàng. 😊";
                } else {
                    // [Best Practice] Sử dụng CartService để đảm bảo tính nhất quán (4.1.1)
                    $this->cartService->addToCart($userId, $productId, $quantity);
                    
                    $msgText = "Ngọc đã thêm {$quantity} chiếc **{$product->name}** vào giỏ hàng cho bạn rồi nhé! 🛒✨";
                    $action = 'cart_updated';
                }

                $assistantMsg = AIChat::create(['user_id' => $userId, 'role' => 'assistant', 'message_content' => $msgText]);
                return response()->json(array_merge($assistantMsg->toArray(), ['action' => $action ?? null]));
            }
        }

        // Trường hợp AI trả về text bình thường
        $msgText = $aiResponse['content'] ?? "Xin lỗi, tôi không tìm thấy câu trả lời.";
        $assistantMsg = AIChat::create(['user_id' => $userId, 'role' => 'assistant', 'message_content' => $msgText]);
        return response()->json($assistantMsg);
    }

    /**
     * Xóa sạch lịch sử chat của người dùng
     */
    public function clear(Request $request)
    {
        AIChat::where('user_id', $request->user()->id)->delete();
        return response()->json(['message' => 'Lịch sử chat đã được xóa.']);
    }
}
