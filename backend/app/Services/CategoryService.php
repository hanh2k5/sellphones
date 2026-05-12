<?php

namespace App\Services;

use App\Models\Category;
use Exception;

class CategoryService
{
    /**
     * [Đặng Văn Hà - 4.3.4] Lấy danh sách danh mục (Dạng phẳng)
     */
    public function getAllFlat()
    {
        return Category::select('id', 'name')->orderBy('name')->get();
    }

    /**
     * Lấy danh sách danh mục theo cấu trúc cây đệ quy (Cha - Con)
     */
    public function getTree()
    {
        return Category::whereNull('parent_id')
            ->with('children.children')
            ->orderBy('name')
            ->get();
    }

    public function getAll()
    {
        return Category::orderBy('name')->get();
    }

    /**
     * [Đặng Văn Hà - 4.3.1] Tạo danh mục mới
     */
    public function create(array $data)
    {
        return Category::create($data);
    }

    /**
     * [Đặng Văn Hà - 4.3.2] Cập nhật danh mục
     */
    public function update(Category $category, array $data)
    {
        $category->update($data);
        return $category;
    }

    /**
     * [Đặng Văn Hà - 4.3.3] Xóa danh mục
     */
    public function delete(Category $category)
    {
        if ($category->products()->count() > 0) {
            throw new Exception(__('messages.category_has_products'), 422);
        }
        return $category->delete();
    }
}
