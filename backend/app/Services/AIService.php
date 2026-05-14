<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [Phan Đình Hạnh - 4.1.11 & 4.1.12] AI Service - Bản Tự Động Phục Hồi (Auto-Failover)
 */
class AIService
{
    protected $apiKey;
    protected $primaryModel = "gemini-2.5-flash-lite";
    protected $fallbackModels = [
        "gemini-3.1-flash-lite",
        "gemini-3.1-flash-image-preview", 
        "gemini-3-flash-preview",
        "gemini-3.1-pro-preview"
    ];

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
    }

    public function chat($userMessage, $history = [])
    {
        $products = Product::select('id', 'name', 'price', 'stock')->where('stock', '>', 0)->limit(15)->get();
        $productContext = "DANH SÁCH SẢN PHẨM:\n";
        foreach ($products as $p) {
            $productContext .= "- ID: {$p->id} | {$p->name} | Giá: " . number_format($p->price) . "đ | Kho: {$p->stock}\n";
        }

        $systemInstruction = [
            'parts' => [['text' => "Bạn là Ngọc, trợ lý Sellphones. 
            NHIỆM VỤ:
            1. Tư vấn sản phẩm chuyên nghiệp, ngắn gọn, có Emoji.
            2. LUÔN trả lời bằng ngôn ngữ người dùng đang hỏi (Việt/Anh).
            3. Dùng 'add_to_cart' khi khách muốn mua.
            4. Không chào hỏi lặp lại.
            Data: $productContext"]]
        ];

        $contents = [];
        foreach ($history as $msg) {
            $contents[] = [
                'role' => ($msg['role'] === 'user') ? 'user' : 'model',
                'parts' => [['text' => $msg['message_content']]]
            ];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        $tools = [['function_declarations' => [[
            'name' => 'add_to_cart',
            'description' => 'Thêm sản phẩm vào giỏ hàng.',
            'parameters' => [
                'type' => 'OBJECT',
                'properties' => [
                    'product_id' => ['type' => 'NUMBER'],
                    'quantity' => ['type' => 'NUMBER']
                ],
                'required' => ['product_id', 'quantity']
            ]
        ]]]];

        // Danh sách các model để thử (Model 2.5 của bạn đứng đầu)
        $modelsToTry = array_merge([$this->primaryModel], $this->fallbackModels);

        foreach ($modelsToTry as $modelName) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$this->apiKey}";
                $response = Http::post($url, [
                    'contents' => $contents,
                    'system_instruction' => $systemInstruction,
                    'tools' => $tools
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $candidate = $data['candidates'][0] ?? null;
                    if (isset($candidate['content']['parts'][0]['functionCall'])) {
                        return ['type' => 'function_call', 'function' => $candidate['content']['parts'][0]['functionCall']];
                    }
                    return ['type' => 'text', 'content' => $candidate['content']['parts'][0]['text'] ?? "Xin lỗi..."];
                }

                // Nếu lỗi 429 hoặc 404, thử model tiếp theo
                Log::warning("Model {$modelName} failed ({$response->status()}), trying next...");
                continue;

            } catch (\Exception $e) {
                Log::error("Error with model {$modelName}: " . $e->getMessage());
                continue;
            }
        }

        return ['type' => 'text', 'content' => "⚠️ Tất cả Model đều đang bận hoặc hết lượt. Bạn vui lòng thử lại sau nhé!"];
    }
}
