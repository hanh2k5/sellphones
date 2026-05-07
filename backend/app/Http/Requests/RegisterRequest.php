<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:50',
            'email'    => 'required|email|unique:users,email',
            'address'  => 'nullable|string|max:255',
            'password' => 'required|string|min:8|regex:/^\S*$/|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'        => 'Vui lòng nhập họ tên.',
            'name.max'             => 'Họ tên không được quá 50 ký tự.',
            'email.required'       => 'Vui lòng nhập email.',
            'email.email'          => 'Email không hợp lệ.',
            'email.unique'         => 'Email này đã được sử dụng, vui lòng chọn email khác.',
            'password.required'    => 'Vui lòng nhập mật khẩu.',
            'password.min'         => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.regex'       => 'Mật khẩu không được chứa khoảng trắng.',
            'password.confirmed'   => 'Xác nhận mật khẩu không khớp.',
        ];
    }
}
