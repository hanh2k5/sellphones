<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (trim($value) === '') {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'code' => ['Vui lòng nhập mã giảm giá.']
                        ]);
                    }
                    if (strlen($value) > 20 || preg_match('/[^A-Za-z0-9]/', $value)) {
                        abort(404, 'Mã giảm giá không hợp lệ.');
                    }
                }
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã giảm giá.',
            'code.string'   => 'Mã giảm giá không hợp lệ.',
        ];
    }
}
