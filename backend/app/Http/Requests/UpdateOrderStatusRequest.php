<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'          => 'required|string|in:pending,confirmed,shipping,shipped,cancelled',
            'last_updated_at' => 'required|string',
        ];
    }
}
