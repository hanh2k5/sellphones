<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [Phan Đình Hạnh - 4.1.11 STT 2] Dịch vụ AI tích hợp dữ liệu sản phẩm thực tế
 */
class AIService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent";
    }

    public function chat($userMessage, $history = [])
    {
        // Nhúng dữ liệu sản phẩm (STT 2)
        $products = Product::select('name', 'price', 'stock')->where('stock', '>', 0)->limit(20)->get();
        $productContext = "Dữ liệu sản phẩm thực tế:\n";
        foreach ($products as $p) {
            $productContext .= "- {$p->name}: Giá " . number_format($p->price) . "đ, Còn {$p->stock} máy.\n";
        }

        $systemPrompt = "You are 'Ngọc' - a professional and friendly AI Assistant for Sellphones store.
        RULES:
        1. LANGUAGE: Respond in the SAME language as the user (English if they ask in English, Vietnamese if they ask in Vietnamese).
        2. EMOTIONS: Use appropriate emojis (😊, 📱, ✨, 📦) to make the conversation friendly and human-like.
        3. DATA: Only provide info based on the provided product list.
        
        REAL-TIME DATA:
        $productContext";

        $contents = [];
        foreach ($history as $msg) {
            $contents[] = [
                'role' => ($msg['role'] === 'user') ? 'user' : 'model',
                'parts' => [['text' => $msg['message_content']]]
            ];
        }

        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => "Ngữ cảnh: $systemPrompt\n\nCâu hỏi: $userMessage"]]
        ];

        try {
            $response = Http::post("{$this->apiUrl}?key={$this->apiKey}", [
                'contents' => $contents,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['candidates'][0]['content']['parts'][0]['text'] ?? "AI không trả về nội dung.";
            }

            // Log lỗi chi tiết từ Google (Rất quan trọng)
            Log::error("Gemini API Fail: " . $response->status() . " - " . $response->body());
            return "Hệ thống AI đang bận.";
        } catch (\Exception $e) {
            Log::error("AI Service Exception: " . $e->getMessage());
            return "Lỗi kết nối AI.";
        }
    }
}
