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
            'email'    => 'required|string|email|max:100|unique:users,email',
            'password' => 'required|string|min:8|max:255|confirmed|not_regex:/\s/',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique'         => 'Email này đã được đăng ký, vui lòng sử dụng email khác.',
            'password.min'         => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.not_regex'   => 'Mật khẩu không được chứa khoảng trắng.',
            'password.confirmed'   => 'Mật khẩu xác nhận không khớp.',
        ];
    }
}
