<?php

namespace App\Services;

use App\Exceptions\StockException;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Lấy toàn bộ thông tin giỏ hàng: phân trang items + tổng tiền + tổng số lượng.
     * Di chuyển từ CartController@index để giữ controller mỏng.
     *
     * @return array{items: \Illuminate\Pagination\LengthAwarePaginator, totalAmount: float, totalQuantity: int}
     */
    public function getCartDetails(int $userId): array
    {
        $query    = CartItem::where('user_id', $userId)->with('product');
        $allItems = (clone $query)->get();

        // Tính tổng dựa trên TOÀN BỘ giỏ (không bị ảnh hưởng bởi phân trang)
        $totalAmount   = $allItems->sum(fn($i) => $i->product->price * $i->quantity);
        $totalQuantity = $allItems->sum('quantity');
        $items         = $query->paginate(10);

        return compact('items', 'totalAmount', 'totalQuantity');
    }

    /**
     * [Phan Đình Hạnh - 4.1.1] Thêm sản phẩm vào giỏ hàng.
     * [Phan Đình Hạnh - 4.1.2] Ràng buộc: kiểm tra tồn kho (Pessimistic Locking).
     * Throws StockException (kèm stock hiện tại) thay vì abort() để Controller không cần query thêm.
     */
    public function addToCart(int $userId, int $productId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($userId, $productId, $quantity) {
            $quantity = max(1, (int) $quantity);

            // Pessimistic Locking: khóa dòng sản phẩm trước khi kiểm tra tồn kho
            $product = Product::where('id', $productId)->lockForUpdate()->firstOrFail();

            if (!$product->is_active) {
                throw new StockException('Sản phẩm hiện không còn kinh doanh.', 0);
            }

            $item             = CartItem::where('user_id', $userId)->where('product_id', $productId)->lockForUpdate()->first();
            $currentQtyInCart = $item ? $item->quantity : 0;

            if ($product->stock <= 0) {
                throw new StockException('Sản phẩm vừa hết hàng.', 0);
            }
            if ($currentQtyInCart + $quantity > $product->stock) {
                throw new StockException('Kho hàng không đủ đáp ứng.', $product->stock);
            }

            if ($item) {
                $item->update(['quantity' => $currentQtyInCart + $quantity]);
                return $item;
            }

            return CartItem::create(['user_id' => $userId, 'product_id' => $productId, 'quantity' => $quantity]);
        });
    }

    /**
     * Cập nhật số lượng giỏ hàng.
     * Throws StockException nếu vượt tồn kho.
     */
    public function updateQuantity(int $userId, $cartItemId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($userId, $cartItemId, $quantity) {
            $item    = CartItem::where('user_id', $userId)->findOrFail($cartItemId);
            $product = Product::where('id', $item->product_id)->lockForUpdate()->firstOrFail();

            $quantity = max(1, (int) $quantity);

            if ($product->stock <= 0) {
                throw new StockException('Sản phẩm vừa hết hàng.', 0);
            }
            if ($quantity > $product->stock) {
                throw new StockException('Kho hàng không đủ đáp ứng.', $product->stock);
            }

            $item->update(['quantity' => $quantity]);
            return $item;
        });
    }

    /**
     * [Phan Đình Hạnh - 4.1.3] Xóa sản phẩm khỏi giỏ.
     */
    public function removeItem(int $userId, $cartItemId): bool
    {
        $cartItem = CartItem::find($cartItemId);
        if (!$cartItem) {
            return true; // Idempotent: đã xóa rồi thì OK
        }
        if ($cartItem->user_id !== $userId) {
            abort(403, 'Bạn không có quyền sở hữu giỏ hàng này.');
        }
        return $cartItem->delete();
    }

    /**
     * Làm trống toàn bộ giỏ hàng của user (gọi sau khi đặt hàng thành công).
     */
    public function clearCart(int $userId): void
    {
        CartItem::where('user_id', $userId)->delete();
    }
}
