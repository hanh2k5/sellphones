<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['user_id', 'product_id', 'quantity'];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lấy giỏ hàng của user kèm sản phẩm (Báo cáo 4.1.1)
     */
    public static function getForUser($userId)
    {
        return self::where('user_id', $userId)
            ->whereHas('product', fn($q) => $q->whereNull('deleted_at'))
            ->with(['product.category'])
            ->get();
    }

    /**
     * Tìm item trong giỏ (phục vụ lock)
     */
    public static function findItem($userId, $productId)
    {
        return self::where('user_id', $userId)
            ->where('product_id', $productId);
    }
}
