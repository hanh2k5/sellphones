<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required',
            'quantity'   => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.min'     => 'Số lượng phải từ 1 trở lên.',
            'quantity.integer' => 'Số lượng phải từ 1 trở lên.',
        ];
    }
}
