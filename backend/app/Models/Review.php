<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = ['user_id', 'product_id', 'order_id', 'rating', 'comment', 'status', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function setCommentAttribute($value): void
    {
        $this->attributes['comment'] = self::sanitizeComment($value);
    }

    public static function sanitizeComment($value): string
    {
        $comment = (string) ($value ?? '');

        for ($i = 0; $i < 3; $i++) {
            $decoded = html_entity_decode($comment, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $comment) {
                break;
            }
            $comment = $decoded;
        }

        $comment = preg_replace('/<\s*(script|style)\b[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $comment);
        $comment = strip_tags($comment);
        $comment = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $comment);

        return trim($comment);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
