<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Cho phép tất cả user đã đăng nhập thực hiện
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Ràng buộc dữ liệu theo đúng báo cáo 4.1.2 STT 2
     */
    public function rules(): array
    {
        return [
            'voucher_code'     => 'nullable|string|exists:vouchers,code',
            'payment_method'   => 'required|in:cod,momo',
            'shipping_address' => 'required|string|max:500',
            'receiver_name'    => 'required|string|max:50',
            'phone'            => 'required|string|regex:/^0[0-9]{9}$/',
        ];
    }

    /**
     * Thông báo lỗi bằng Tiếng Việt chuẩn
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Số điện thoại không hợp lệ (phải gồm 10 chữ số và bắt đầu bằng số 0).',
            'receiver_name.max' => 'Họ tên không được quá 50 ký tự.',
        ];
    }
}
