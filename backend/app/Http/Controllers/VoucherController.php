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
            
            $user = $request->user();
            
            // Validate if user has already used it
            if ($user) {
                $alreadyUsed = \App\Models\Order::where('user_id', $user->id)
                    ->where('voucher_id', $voucher->id)
                    ->where('status', '!=', 'cancelled')
                    ->exists();
                if ($alreadyUsed) {
                    return response()->json(['message' => 'Bạn đã sử dụng mã giảm giá này cho một đơn hàng khác.'], 422);
                }
            }

            // Tính toán tổng tiền giỏ hàng hiện tại để kiểm tra min_order_value
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
    public function index(Request $request)
    {
        $vouchers = $this->voucherService->getActiveVouchers();
        $user = $request->user('sanctum');

        if ($user) {
            $usedVoucherIds = \App\Models\Order::where('user_id', $user->id)
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('voucher_id')
                ->pluck('voucher_id')
                ->toArray();
                
            $vouchers = $vouchers->filter(function($voucher) use ($usedVoucherIds) {
                return !in_array($voucher->id, $usedVoucherIds);
            });
        }

        return VoucherResource::collection($vouchers);
    }
}
