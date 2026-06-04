<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminStoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:50',
            'email'    => 'required|email|max:100|unique:users',
            'password' => 'required|min:8',
            'phone'    => 'nullable|string|max:15',
            'address'  => 'nullable|string|max:500',
            'role'     => 'required|in:admin,user',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Email này đã được sử dụng, vui lòng chọn email khác.',
            'password.min' => 'Mật khẩu phải chứa ít nhất 8 ký tự.',
            'role.in'      => 'Vai trò không hợp lệ.',
        ];
    }
}
