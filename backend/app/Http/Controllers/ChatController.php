<?php

namespace App\Http\Controllers;

use App\Models\AIChat;
use App\Services\AIService;
use Illuminate\Http\Request;

/**
 * [Phan Đình Hạnh - 4.1.11 STT 1 & 3] Quản lý hội thoại Chatbot
 */
class ChatController extends Controller
{
    protected $aiService;

    public function __construct(AIService $aiService)
    {
        $this->aiService = $aiService;
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
        
        AIChat::create(['user_id' => $userId, 'role' => 'user', 'message_content' => $request->message]);

        $history = AIChat::where('user_id', $userId)->orderBy('created_at', 'desc')->limit(10)->get()->reverse()->toArray();
        $aiResponse = $this->aiService->chat($request->message, $history);

        $assistantMsg = AIChat::create(['user_id' => $userId, 'role' => 'assistant', 'message_content' => $aiResponse]);
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
