<?php

namespace App\Http\Controllers;

use App\Services\VoucherService;
use App\Models\CartItem;
use App\Http\Resources\VoucherResource;
use Illuminate\Http\Request;
use Exception;

class VoucherController extends Controller
{
    protected $voucherService;

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * API Áp dụng mã giảm giá vào giỏ hàng.
     * Kiểm tra tính hợp lệ và giá trị đơn hàng tối thiểu.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function apply(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        try {
            $voucher = $this->voucherService->validateCode($request->code);
            
            // Tính toán tổng tiền giỏ hàng hiện tại để kiểm tra min_order_value
            $user = $request->user();
            $cartItems = CartItem::where('user_id', $user->id)->with('product')->get();
            $totalAmount = $cartItems->sum(fn($item) => $item->product->price * $item->quantity);

            if ($totalAmount < $voucher->min_order_value) {
                return response()->json([
                    'message' => __('messages.min_order_error', ['min' => number_format($voucher->min_order_value, 0, ',', '.') . 'đ'])
                ], 422);
            }

            $discount = $voucher->calculateDiscount($totalAmount);

            return response()->json([
                'voucher'  => (new VoucherResource($voucher))->resolve(),
                'discount' => $discount,
                'message'  => __('messages.voucher_applied')
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    /**
     * Lấy danh sách voucher công khai
     */
    public function index()
    {
        $vouchers = $this->voucherService->getActiveVouchers();
        return VoucherResource::collection($vouchers);
    }
}
