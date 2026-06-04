<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\Request;

/**
 * [Phan Đình Hạnh - 4.1.1 → 4.1.3] CartController
 * LUỒNG GIỎ HÀNG:
 *   User bấm "Thêm giỏ" → store() → CartService@addToCart → upsert bảng cart_items
 *   Vào trang giỏ hàng   → index() → CartItem::where(user_id) + tính tổng tiền
 *   Sửa số lượng        → update() → CartService@updateQuantity → kiểm tra tồn kho
 *   Xóa 1 sản phẩm      → destroy() → CartService@removeItem → xóa dòng cart_items
 *   Xóa toàn bộ giỏ     → clear()  → CartService@clearCart  → sau khi đặt hàng thành công
 */
class CartController extends Controller
{
    protected $cartService; // CartService xử lý logic kiểm tra tồn kho & upsert

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * GET /cart → lấy toàn bộ giỏ hàng của user đang đăng nhập, phân trang 10/trang.
     * Trả về: {items (paginated), total_amount, total_quantity}
     */
    public function index(Request $request)
    {
        $query = CartItem::where('user_id', $request->user()->id)
            ->with('product'); // Eager load sản phẩm → hiển thị tên, giá, ảnh
            
        // Tính tổng tiền + số lượng dựa trên TOÀN BỘ giỏ (không bị ảnh hưởng bởi phân trang)
        $allItems      = (clone $query)->get();
        $totalAmount   = $allItems->sum(fn($item) => $item->product->price * $item->quantity);
        $totalQuantity = $allItems->sum('quantity');

        $items = $query->paginate(10); // Phân trang 10 SP/trang để hiển thị

        return response()->json([
            'items'          => $items,
            'total_amount'   => $totalAmount,
            'total_quantity' => $totalQuantity
        ]);
    }

    /**
     * [4.1.1] POST /cart → thêm SP vào giỏ.
     * [4.1.2] CartService kiểm tra: số lượng yêu cầu ≤ tồn kho thực tế.
     * Nếu SP đã có trong giỏ → cộng thêm (upsert), không tạo dòng trùng.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id', // SP phải tồn tại trong DB
            'quantity'   => 'required|integer|min:1',
        ]);

        $item = $this->cartService->addToCart(
            $request->user()->id,
            $request->product_id,
            $request->quantity
        );

        return response()->json([
            'message' => 'Đã thêm vào giỏ hàng!',
            'item'    => $item->load('product'), // Trả kèm thông tin SP để Frontend cập nhật UI
        ]);
    }

    /**
     * PUT /cart/{id} → cập nhật số lượng một item trong giỏ.
     * CartService kiểm tra quantity mới ≤ tồn kho → cập nhật hoặc báo lỗi.
     */
    public function update(Request $request, $id)
    {
        $request->validate(['quantity' => 'required|integer|min:1']);

        $item = $this->cartService->updateQuantity(
            $request->user()->id,
            $id,
            $request->quantity
        );

        return response()->json([
            'message' => 'Đã cập nhật số lượng!',
            'item'    => $item->load('product'),
        ]);
    }

    /**
     * [4.1.3] DELETE /cart/{id} → xóa 1 dòng khỏi cart_items (theo cartItemId, không phải productId).
     */
    public function destroy(Request $request, $id)
    {
        $this->cartService->removeItem($request->user()->id, $id);
        return response()->json(['message' => 'Đã xóa sản phẩm khỏi giỏ hàng.']);
    }

    /** DELETE /cart/clear → xóa toàn bộ cart_items của user → gọi sau khi đặt hàng thành công */
    public function clear(Request $request)
    {
        $this->cartService->clearCart($request->user()->id);
        return response()->json(['message' => 'Đã làm trống giỏ hàng.']);
    }
}
