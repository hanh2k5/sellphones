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
     * [Phan Đình Hạnh - 4.1.2 STT 2] Ràng buộc dữ liệu đặt hàng
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
     * Tùy chỉnh thông báo lỗi (Sử dụng Localization để hỗ trợ cả Anh/Việt)
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('validation.phone_regex'),
            'receiver_name.max' => __('validation.max.string', ['attribute' => __('validation.attributes.receiver_name'), 'max' => 50]),
        ];
    }

    /**
     * Định nghĩa tên hiển thị của các trường dữ liệu
     */
    public function attributes(): array
    {
        return [
            'voucher_code'     => __('validation.attributes.voucher_code'),
            'payment_method'   => __('validation.attributes.payment_method'),
            'shipping_address' => __('validation.attributes.shipping_address'),
            'receiver_name'    => __('validation.attributes.receiver_name'),
            'phone'            => __('validation.attributes.phone'),
        ];
    }
}
