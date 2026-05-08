<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $user = Auth::user();
        $productId = $request->product_id;
        $quantity = $request->quantity ?? 1;

        // Kiểm tra xem sản phẩm đã có trong giỏ chưa
        $cartItem = CartItem::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($cartItem) {
            // Đã có -> Tăng số lượng
            $cartItem->increment('quantity', $quantity);
        } else {
            // Chưa có -> Tạo mới
            $cartItem = CartItem::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'data' => $cartItem
        ]);
    }

    /**
     * Lấy danh sách giỏ hàng (Để hiện số lượng ở Navbar)
     */
    public function index()
    {
        $items = CartItem::where('user_id', Auth::id())
            ->with('product')
            ->get();
            
        return response()->json([
            'success' => true,
            'data' => $items,
            'total_items' => $items->sum('quantity')
        ]);
    }
}
