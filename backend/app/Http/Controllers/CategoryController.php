<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Http\Request;
use Exception;

/**
 * SV THỰC HIỆN: ĐẶNG VĂN HÀ
 * MỤC: 4.3.1 → 4.3.4 (QUẢN LÝ DANH MỤC)
 * Thin Controller: không có Category::query trực tiếp, không có htmlspecialchars trong Controller.
 * CategoryService@getPaginated xử lý query phân trang.
 * CategoryService@sanitizeName xử lý HTML sanitization (đã tích hợp vào create/update).
 */
class CategoryController extends Controller
{
    public function __construct(protected CategoryService $categoryService) {}

    public function index(Request $request)
    {
        $activeOnly = !$request->has('all');

        // Yêu cầu phân trang → ủy thác CategoryService@getPaginated
        if ($request->has('paginate') || $request->has('page')) {
            $categories = $this->categoryService->getPaginated($request->all(), $activeOnly);
            return response()->json($categories);
        }

        // Cấu trúc cây → CategoryService@getTree
        return response()->json($this->categoryService->getTree($activeOnly));
    }

    /**
     * [Đặng Văn Hà - 4.3.4] Hiển thị danh sách danh mục (Flat)
     */
    public function flat(Request $request)
    {
        $activeOnly = !$request->has('all');
        return response()->json($this->categoryService->getAllFlat($activeOnly));
    }

    /**
     * [Đặng Văn Hà - 4.3.1] Thêm mới danh mục.
     * HTML sanitization đã được CategoryService@create xử lý.
     */
    public function store(StoreCategoryRequest $request)
    {
        try {
            $category = $this->categoryService->create($request->validated());
            return response()->json($category, 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Lỗi kết nối máy chủ, chưa lưu được.'], 500);
        }
    }

    /**
     * HTML sanitization đã được CategoryService@update xử lý.
     */
    public function update(UpdateCategoryRequest $request, \App\Models\Category $category)
    {
        try {
            $updatedCategory = $this->categoryService->update($category, $request->validated());
            return response()->json($updatedCategory);
        } catch (Exception $e) {
            return response()->json(['message' => 'Lỗi kết nối máy chủ, chưa lưu được.'], 500);
        }
    }

    public function destroy(\App\Models\Category $category)
    {
        try {
            $this->categoryService->delete($category);
            return response()->json(['message' => 'Xóa danh mục thành công.']);
        } catch (Exception $e) {
            if ($e->getCode() === 422) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return response()->json(['message' => 'Lỗi hệ thống, vui lòng thử lại sau.'], 500);
        }
    }
}
