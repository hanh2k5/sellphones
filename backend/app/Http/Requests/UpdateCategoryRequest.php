<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Category;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $categoryId = $category ? $category->id : null;

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
            ],
            'parent_id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($value !== null && $value !== '') {
                        $parent = Category::find($value);
                        if (!$parent) {
                            $fail('Danh mục cha không tồn tại.');
                            return;
                        }
                        
                        if ($value == $categoryId || $this->isDescendant($value, $categoryId)) {
                            $fail('Danh mục con không được phép chọn chính nó làm cha');
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

    private function isDescendant($parentCandidateId, $categoryId)
    {
        if (!$parentCandidateId) {
            return false;
        }
        if ($parentCandidateId == $categoryId) {
            return true;
        }
        $parent = Category::find($parentCandidateId);
        if ($parent && $parent->parent_id) {
            return $this->isDescendant($parent->parent_id, $categoryId);
        }
        return false;
    }
}
