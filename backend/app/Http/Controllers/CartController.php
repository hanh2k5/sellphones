<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Services\CartService;
use Illuminate\Http\Request;

/**
 * SV THỰC HIỆN: PHAN ĐÌNH HẠNH
 * MỤC: 4.1.1 -> 4.1.3 (GIỎ HÀNG)
 */
class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index(Request $request)
    {
        $items = CartItem::where('user_id', $request->user()->id)
            ->with('product')
            ->get();
            
        $totalAmount = $items->sum(fn($item) => $item->product->price * $item->quantity);
        $totalQuantity = $items->sum('quantity');

        return response()->json([
            'items'          => $items,
            'total_amount'   => $totalAmount,
            'total_quantity' => $totalQuantity
        ]);
    }

    /**
     * [Phan Đình Hạnh - 4.1.1] Thêm sản phẩm vào giỏ hàng
     * [Phan Đình Hạnh - 4.1.2] Ràng buộc: Kiểm tra tồn kho thực tế
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $item = $this->cartService->addToCart(
            $request->user()->id, 
            $request->product_id, 
            $request->quantity
        );

        return response()->json([
            'message' => 'Đã thêm vào giỏ hàng!',
            'item'    => $item->load('product'),
        ]);
    }

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
     * [Phan Đình Hạnh - 4.1.3] Xóa sản phẩm khỏi giỏ hàng
     */
    public function destroy(Request $request, $id)
    {
        $this->cartService->removeItem($request->user()->id, $id);
        return response()->json(['message' => 'Đã xóa sản phẩm khỏi giỏ hàng.']);
    }

    public function clear(Request $request)
    {
        $this->cartService->clearCart($request->user()->id);
        return response()->json(['message' => 'Đã làm trống giỏ hàng.']);
    }
}
