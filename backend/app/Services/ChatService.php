<?php

namespace App\Services;

use App\Models\AIChat;
use Illuminate\Support\Collection;

/**
 * ChatService — CRUD lịch sử hội thoại AI (AIChat model).
 * Tách biệt khỏi AIService (xử lý prompt/model) theo SRP.
 */
class ChatService
{
    /**
     * Lấy toàn bộ lịch sử chat của user (tối đa 50 tin nhắn, sắp xếp ASC).
     */
    public function getHistory(int $userId): Collection
    {
        return AIChat::where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();
    }

    /**
     * Lấy lịch sử gần đây nhất để gửi lên AI làm context (sắp xếp ASC cho AI đọc).
     */
    public function getRecentHistory(int $userId, int $limit = 10): array
    {
        return AIChat::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->toArray();
    }

    /**
     * Lưu một tin nhắn mới vào lịch sử chat.
     */
    public function saveMessage(int $userId, string $role, string $content): AIChat
    {
        return AIChat::create([
            'user_id'         => $userId,
            'role'            => $role,
            'message_content' => $content,
        ]);
    }

    /**
     * Xóa toàn bộ lịch sử chat của user.
     */
    public function clearHistory(int $userId): void
    {
        AIChat::where('user_id', $userId)->delete();
    }
}
