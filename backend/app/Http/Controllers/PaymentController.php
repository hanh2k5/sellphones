<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * SV THỰC HIỆN: PHAN ĐÌNH HẠNH
 * MỤC: 4.1.14 (THANH TOÁN MOMO GIẢ LẬP)
 */
class PaymentController extends Controller
{
    // Fake MoMo: tạo link thanh toán
    public function createMomo(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'so_tien' => 'required|integer|min:1000',
        ]);

        // Fake MoMo transaction ID
        $transactionId = 'MOMO' . time() . rand(1000, 9999);

        return response()->json([
            'message' => 'Tạo giao dịch MoMo thành công!',
            'transaction_id' => $transactionId,
            'order_id' => $request->order_id,
            'so_tien' => $request->so_tien,
            'redirect_url' => "/payment/momo?tid={$transactionId}&amount={$request->so_tien}&order_id={$request->order_id}",
            'qr_data' => "MOMO_QR_{$transactionId}",
        ]);
    }

    // Fake MoMo: callback sau khi thanh toán (giả lập success)
    public function momoCallback(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Thanh toán MoMo thành công!',
            'transaction_id' => $request->tid,
            'order_id' => $request->order_id,
        ]);
    }
}
