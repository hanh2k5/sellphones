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

    protected function prepareForValidation()
    {
        if ($this->has('name')) {
            $name = $this->input('name');
            if (is_string($name)) {
                $name = preg_replace('/　/u', ' ', $name);
                $name = trim($name);
            }
            $this->merge([
                'name' => $name,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:150|unique:products,name,' . ($this->route('product')?->id ?? 'NULL'),
            'category_id'   => 'required|exists:categories,id',
            'price'         => 'required|numeric|min:0',
            'stock'         => 'required|integer|min:0',
            'hinh_anh'      => 'nullable|string',
            'hinh_anh_file' => 'nullable|file|max:2048|mimes:jpg,png',
            'description'   => 'nullable|string',
            'is_active'     => 'nullable|boolean',
            'is_featured'   => 'nullable|boolean',
            // Khi sửa, frontend gửi updated_at để chống 2 tab ghi đè nhau.
            'updated_at'    => $this->isMethod('put') ? 'required|date' : 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Vui lòng nhập tên sản phẩm.',
            'name.max' => 'Tên sản phẩm không được vượt quá 150 ký tự.',
            'name.unique' => 'Tên sản phẩm này đã tồn tại.',
            'price.required' => 'Vui lòng nhập giá sản phẩm.',
            'price.numeric' => 'Giá sản phẩm phải là một con số.',
            'price.min' => 'Giá và số lượng phải lớn hơn hoặc bằng 0.',
            'stock.required' => 'Vui lòng nhập số lượng tồn kho.',
            'stock.min' => 'Giá và số lượng phải lớn hơn hoặc bằng 0.',
            'category_id.required' => 'Vui lòng phân loại danh mục cho sản phẩm.',
            'category_id.exists' => 'Danh mục đã chọn không tồn tại.',
        ];
    }
}
