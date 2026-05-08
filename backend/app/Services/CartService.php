<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;

class CartService
{
    /**
     * Thêm sản phẩm vào giỏ hàng (Xử lý trùng và tồn kho)
     */
    /**
     * [Phan Đình Hạnh - 4.1.1] Thêm sản phẩm vào giỏ hàng
     * [Phan Đình Hạnh - 4.1.2] Ràng buộc: Kiểm tra tồn kho sản phẩm
     */
    public function addToCart($userId, $productId, $quantity)
    {
        $product = Product::findOrFail($productId);
        
        $item = CartItem::where('user_id', $userId)->where('product_id', $productId)->first();

        if ($item) {
            $newQty = min($item->quantity + $quantity, $product->stock);
            $item->update(['quantity' => $newQty]);
            return $item;
        }

        return CartItem::create([
            'user_id'    => $userId,
            'product_id' => $productId,
            'quantity'   => min($quantity, $product->stock),
        ]);
    }

    /**
     * Cập nhật số lượng giỏ hàng
     */
    public function updateQuantity($userId, $cartItemId, $quantity)
    {
        $item = CartItem::where('user_id', $userId)->findOrFail($cartItemId);
        $item->update(['quantity' => max(1, min($quantity, $item->product->stock))]);
        return $item;
    }

    /**
     * Xóa sản phẩm khỏi giỏ
     */
    /**
     * [Phan Đình Hạnh - 4.1.3] Xóa sản phẩm khỏi giỏ hàng
     */
    public function removeItem($userId, $cartItemId)
    {
        return CartItem::where('user_id', $userId)->where('id', $cartItemId)->delete();
    }

    /**
     * Làm trống giỏ hàng
     */
    public function clearCart($userId)
    {
        return CartItem::where('user_id', $userId)->delete();
    }
}
