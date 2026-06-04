<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Category;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    if (strip_tags($value) !== $value || preg_match('/[<>]/', $value)) {
                        $fail('Tên danh mục không hợp lệ hoặc quá dài.');
                    }
                },
                'unique:categories,name',
            ],
            'parent_id' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value !== null && $value !== '') {
                        $parent = Category::find($value);
                        if (!$parent) {
                            $fail('Danh mục cha không tồn tại.');
                            return;
                        }
                        if ($parent->parent_id !== null) {
                            $fail('Danh mục cha được chọn phải là danh mục gốc.');
                        }
                    }
                }
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên danh mục không được để trống/bắt buộc',
            'name.max'      => 'Tên danh mục không hợp lệ hoặc quá dài.',
            'name.unique'   => 'Tên danh mục đã tồn tại.',
        ];
    }
}
