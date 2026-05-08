<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * SV THỰC HIỆN: ĐẶNG VĂN HÀ
 * MỤC: 4.3.1 -> 4.3.4 (QUẢN LÝ DANH MỤC)
 */
class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index()
    {
        return response()->json($this->categoryService->getTree());
    }

    /**
     * [Đặng Văn Hà - 4.3.4] Hiển thị danh sách danh mục (Flat)
     */
    public function flat()
    {
        return response()->json($this->categoryService->getAllFlat());
    }

    /**
     * [Đặng Văn Hà - 4.3.1] Thêm mới danh mục sản phẩm (Categories)
     */
    public function store(Request $request)
    {
        $this->authorizeAdmin();
        $request->validate(['name' => 'required|string|max:255|unique:categories,name']);
        
        $category = $this->categoryService->create($request->all());
        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeAdmin();
        $request->validate(['name' => 'required|string|max:255|unique:categories,name,' . $category->id]);
        
        $updatedCategory = $this->categoryService->update($category, $request->all());
        return response()->json($updatedCategory);
    }

    /**
     * [Đặng Văn Hà - 4.3.3] Xóa danh mục sản phẩm
     */
    public function destroy(Category $category)
    {
        $this->authorizeAdmin();
        try {
            $this->categoryService->delete($category);
            return response()->json(['message' => 'Xóa danh mục thành công.']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function authorizeAdmin()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Bạn không có quyền quản trị.');
        }
    }
}
