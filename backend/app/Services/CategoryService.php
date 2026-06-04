<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Exception;

class CategoryService
{
    /**
     * [Đặng Văn Hà - 4.3.4] Lấy danh sách danh mục (Dạng phẳng)
     */
    public function getAllFlat($activeOnly = false)
    {
        $query = Category::orderBy('name');
        if ($activeOnly) {
            $query->where('is_active', true);
        }
        return $query->get();
    }

    /**
     * Lấy danh sách danh mục theo cấu trúc cây đệ quy (Cha - Con)
     */
    public function getTree($activeOnly = false)
    {
        if ($activeOnly) {
            return Category::whereNull('parent_id')
                ->where('is_active', true)
                ->with('activeChildren')
                ->orderBy('name')
                ->get();
        }
        return Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('name')
            ->get();
    }

    public function getAll()
    {
        return Category::orderBy('name')->get();
    }

    /**
     * [Đặng Văn Hà - 4.3.1] Tạo danh mục mới (tích hợp sanitize HTML)
     */
    public function create(array $data): Category
    {
        $data['name'] = $this->sanitizeName($data['name']);
        return Category::create($data);
    }

    /**
     * [Đặng Văn Hà - 4.3.2] Cập nhật danh mục (tích hợp sanitize HTML)
     */
    public function update(Category $category, array $data): Category
    {
        $data['name'] = $this->sanitizeName($data['name']);
        $category->update($data);
        return $category;
    }

    /**
     * [Đặng Văn Hà - 4.3.3] Xóa danh mục
     */
    public function delete(Category $category): bool
    {
        return DB::transaction(function () use ($category) {
            if ($category->products()->exists()) {
                throw new Exception('Không thể xóa! Danh mục này vẫn còn chứa sản phẩm.', 422);
            }
            if ($category->children()->exists()) {
                throw new Exception('Vui lòng xóa hoặc di chuyển các danh mục con trước.', 422);
            }
            return $category->delete();
        });
    }

    /**
     * Lấy danh mục phân trang (flat list) với tìm kiếm.
     * Di chuyển từ CategoryController@index để loại bỏ truy vấn Model trực tiếp trong Controller.
     */
    public function getPaginated(array $filters, bool $activeOnly = true): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Category::query();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        } else {
            $query->whereNull('parent_id');
        }

        $perPage = $filters['per_page'] ?? 10;
        return $query->with(['parent', 'children'])->orderBy('name')->paginate($perPage);
    }

    /**
     * Sanitize tên danh mục: loại bỏ HTML tags và encode ký tự đặc biệt.
     * Logic này được di chuyển từ CategoryController vào Service để Controller không chứa business logic.
     */
    public function sanitizeName(string $name): string
    {
        return htmlspecialchars(strip_tags($name), ENT_QUOTES, 'UTF-8');
    }
}


