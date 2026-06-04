<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Quyền hạn đã được check ở Middleware/Policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name'          => [
                'required',
                'string',
                'max:255',
                'not_regex:/ {2,}/',
                $this->isMethod('put') ? 'unique:products,name,' . $id : 'unique:products,name'
            ],
            'category_id'   => 'required|exists:categories,id',
            'price'         => 'required|integer|min:0',
            'stock'         => 'required|integer|min:0',
            'hinh_anh'      => 'nullable|string',
            'description'   => 'nullable|string|max:5000',
            'is_active'     => 'nullable|boolean',
            // Khi sửa, frontend gửi updated_at để chống 2 tab ghi đè nhau.
            'updated_at'    => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.unique' => 'Tên sản phẩm này đã tồn tại trong hệ thống.',
            'name.max' => 'Tên sản phẩm không được vượt quá 255 ký tự.',
            'name.not_regex' => 'Tên sản phẩm không được chứa nhiều khoảng trắng liên tiếp.',
            'price.required' => 'Vui lòng nhập giá sản phẩm.',
            'price.integer' => 'Giá sản phẩm phải là một số nguyên (không có chữ và số thập phân).',
            'price.min' => 'Giá sản phẩm không được nhỏ hơn 0.',
            'stock.required' => 'Vui lòng nhập số lượng tồn kho.',
            'stock.integer' => 'Số lượng tồn kho phải là số nguyên.',
            'stock.min' => 'Số lượng tồn kho không được nhỏ hơn 0.',
            'category_id.required' => 'Vui lòng chọn danh mục.',
            'category_id.exists' => 'Danh mục đã chọn không tồn tại.',
            'description.max' => 'Mô tả sản phẩm không được vượt quá 5000 ký tự.',
        ];
    }
}
