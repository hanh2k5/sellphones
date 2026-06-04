<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'rating'   => 'required|integer|min:1|max:5',
            'comment'  => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'Vui lòng nhập Order ID!',
            'rating.required'   => 'Vui lòng chọn số sao',
        ];
    }
}
