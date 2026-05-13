<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * [Phan Đình Hạnh - 4.1.11 STT 3] Model lưu trữ lịch sử hội thoại AI
 */
class AIChat extends Model
{
    use HasFactory;

    protected $table = 'ai_chats';
    public $timestamps = false; // Tắt mặc định vì migration chỉ có created_at

    protected $fillable = [
        'user_id',
        'role',
        'message_content',
        'created_at'
    ];

    /**
     * Tự động gán created_at khi tạo mới
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
