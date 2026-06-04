<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => 'required|email',
            'otp'      => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed|not_regex:/\s/',
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required'            => 'Vui lòng nhập mã OTP.',
            'otp.size'                => 'Mã OTP phải có đúng 6 chữ số.',
            'password.min'            => 'Mật khẩu mới phải có ít nhất 8 ký tự.',
            'password.not_regex'      => 'Mật khẩu không được chứa khoảng trắng.',
            'password.confirmed'      => 'Mật khẩu xác nhận không khớp.',
            'email.required'          => 'Vui lòng nhập email.',
            'email.email'             => 'Email không hợp lệ.',
        ];
    }
}
