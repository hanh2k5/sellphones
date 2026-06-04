<?php

namespace App\Http\Controllers;

use App\Services\VoucherService;
use App\Models\CartItem;
use App\Http\Resources\VoucherResource;
use App\Http\Requests\ApplyVoucherRequest;
use Illuminate\Http\Request;
use Exception;

/**
 * [Phan Đình Hạnh - 4.1.13] VoucherController
 * LUỒNG VOUCHER:
 *   User nhập mã → apply() → validate mã → kiểm tra đã dùng chưa → tính tiền giảm
 *   Frontend lưu voucher vào localStorage (cart_voucher) → gửi kèm khi checkout
 *   Trang giỏ hàng → index() → lấy danh sách voucher còn hiệu lực chưa dùng
 */
class VoucherController extends Controller
{
    protected $voucherService; // VoucherService kiểm tra tính hợp lệ & tính tiền giảm

    public function __construct(VoucherService $voucherService)
    {
        $this->voucherService = $voucherService;
    }

    /**
     * POST /vouchers/apply → áp dụng mã giảm giá vào giỏ hàng hiện tại.
     * Kiểm tra theo thứ tự:
     *   1. Mã tồn tại, còn hạn, chưa vượt giới hạn lượt dùng (VoucherService@validateCode)
     *   2. User chưa dùng mã này cho đơn hàng khác (chống lạm dụng)
     *   3. Tổng tiền giỏ hàng ≥ min_order_value của voucher
     * Trả về: {voucher, discount (số tiền được giảm), message}
     */
    public function apply(ApplyVoucherRequest $request)
    {
        $code = $request->input('code');

        try {
            $user = $request->user();
            $result = $this->voucherService->applyVoucher($code, $user);

            return response()->json([
                'voucher'  => (new VoucherResource($result['voucher']))->resolve(),
                'discount' => $result['discount'],  // Frontend lưu giá trị này vào localStorage (cart_discount)
                'message'  => __('messages.voucher_applied')
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    /**
     * GET /vouchers → lấy danh sách voucher đang hoạt động (còn hạn, chưa hết lượt dùng).
     * Lọc bỏ các voucher user hiện tại đã sử dụng → chỉ hiện voucher dùng được.
     */
    public function index(Request $request)
    {
        $vouchers = $this->voucherService->getActiveVouchers(); // Tất cả voucher còn hiệu lực
        $user     = $request->user('sanctum');

        if ($user) {
            // Lấy danh sách ID voucher user này đã dùng (đơn không bị hủy)
            $usedVoucherIds = \App\Models\Order::where('user_id', $user->id)
                ->where('status', '!=', 'cancelled')
                ->whereNotNull('voucher_id')
                ->pluck('voucher_id')
                ->toArray();
                
            // Lọc bỏ voucher đã dùng → chỉ trả về voucher user chưa dùng
            $vouchers = $vouchers->filter(function($voucher) use ($usedVoucherIds) {
                return !in_array($voucher->id, $usedVoucherIds);
            });
        }

        return VoucherResource::collection($vouchers);
    }
}
