<?php

namespace App\Http\Controllers;

use App\Exceptions\StockException;
use App\Services\CartService;
use App\Http\Resources\CartItemResource;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use Illuminate\Http\Request;

/**
 * [Phan Đình Hạnh - 4.1.1 → 4.1.3] CartController
 * Thin Controller: không có CartItem::where, Product::find, business logic.
 * CartService@getCartDetails xử lý toàn bộ query và tính toán giỏ hàng.
 * StockException mang theo stock hiện tại → Controller không cần query thêm.
 */
class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    /**
     * GET /cart → lấy giỏ hàng phân trang kèm tổng tiền và tổng số lượng.
     */
    public function index(Request $request)
    {
        $result = $this->cartService->getCartDetails($request->user()->id);
        $items  = $result['items'];
        $items->setCollection(CartItemResource::collection($items->getCollection())->collection);

        return response()->json([
            'items'          => $items,
            'total_amount'   => $result['totalAmount'],
            'total_quantity' => $result['totalQuantity'],
        ]);
    }

    /**
     * [4.1.1] POST /cart → thêm sản phẩm vào giỏ.
     * StockException chứa stock hiện tại → không cần Product::find trong catch.
     */
    public function store(StoreCartItemRequest $request)
    {
        try {
            $item = $this->cartService->addToCart(
                $request->user()->id,
                $request->product_id,
                $request->quantity
            );
            return response()->json([
                'message' => 'Đã thêm sản phẩm vào giỏ hàng.',
                'item'    => $item->load('product'),
            ]);
        } catch (StockException $e) {
            return response()->json(['message' => $e->getMessage(), 'stock' => $e->getStock()], 422);
        }
    }

    /**
     * PUT /cart/{id} → cập nhật số lượng một item.
     * StockException chứa stock hiện tại → không cần CartItem::find + Product::find trong catch.
     */
    public function update(UpdateCartItemRequest $request, $id)
    {
        try {
            $item = $this->cartService->updateQuantity($request->user()->id, $id, $request->quantity);
            return response()->json([
                'message' => 'Đã cập nhật số lượng!',
                'item'    => $item->load('product'),
            ]);
        } catch (StockException $e) {
            return response()->json(['message' => $e->getMessage(), 'stock' => $e->getStock()], 422);
        }
    }

    /**
     * [4.1.3] DELETE /cart/{id} → xóa 1 item khỏi giỏ.
     */
    public function destroy(Request $request, $id)
    {
        $this->cartService->removeItem($request->user()->id, $id);
        return response()->json(['message' => 'Đã xóa sản phẩm khỏi giỏ hàng.']);
    }

    /**
     * DELETE /cart/clear → xóa toàn bộ giỏ hàng.
     */
    public function clear(Request $request)
    {
        $this->cartService->clearCart($request->user()->id);
        return response()->json(['message' => 'Đã làm trống giỏ hàng.']);
    }
}
