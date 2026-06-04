<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * SV THỰC HIỆN: ĐẶNG VĂN HÀ
 * MỤC: 4.3.5 -> 4.3.15 (QUẢN LÝ SẢN PHẨM)
 */
class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * [Đặng Văn Hà - 4.3.12] Tìm kiếm sản phẩm (Query Like)
     * [Đặng Văn Hà - 4.3.13] Lọc sản phẩm theo giá
     * [Đặng Văn Hà - 4.3.8] Hiển thị danh sách sản phẩm (Phân trang)
     */
    public function index(Request $request)
    {
        // Nhận query từ UI; service/model sẽ xử lý search, lọc giá và phân trang.
        $products = $this->productService->getAllProducts($request->all());
        return ProductResource::collection($products);
    }

    /**
     * [Đặng Văn Hà - 4.3.9] Hiển thị chi tiết sản phẩm (Eager Loading)
     */
    public function show(Product $product)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        // Khách không thấy sản phẩm ngừng bán; admin vẫn được xem để quản lý.
        if (!$product->is_active && (!Auth::check() || !$user->isAdmin())) {
            abort(404);
        }
        // Load sẵn danh mục, ảnh và review đã duyệt để trả chi tiết trong một response.
        $product->load(['category', 'images', 'reviews' => function($query) {
            $query->where('status', 'approved')->with('user:id,name');
        }]);
        return (new ProductResource($product))->resolve();
    }

    /**
     * [Đặng Văn Hà - 4.3.5] Thêm mới sản phẩm (Quản lý dữ liệu và Hình ảnh)
     */
    public function store(ProductRequest $request)
    {
        $this->authorizeAdmin();
        // validated() chỉ lấy dữ liệu đã qua rule trong ProductRequest.
        $product = $this->productService->createProduct($request->validated());
        return (new ProductResource($product))
            ->additional(['message' => 'Thêm sản phẩm thành công!']);
    }

    /**
     * [Đặng Văn Hà - 4.3.6] Sửa thông tin sản phẩm (Xử lý tranh chấp dữ liệu - Optimistic Locking)
     */
    public function update(ProductRequest $request, $id)
    {
        $this->authorizeAdmin();

        $product = Product::find($id);
        if (!$product) {
            $trashed = Product::onlyTrashed()->find($id);
            if ($trashed) {
                return response()->json(['message' => __('messages.data_conflict')], 409);
            }
            return response()->json(['message' => 'Sản phẩm không tồn tại.'], 404);
        }

        try {
            // Service kiểm tra updated_at; nếu dữ liệu cũ sẽ trả lỗi 409.
            $updatedProduct = $this->productService->updateProduct($product, $request->validated());
            return (new ProductResource($updatedProduct))
                ->additional(['message' => 'Cập nhật thành công!']);
        } catch (Exception $e) {
            // Giữ đúng mã lỗi nghiệp vụ, đặc biệt là 409 Conflict.
            $code = is_numeric($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599
                ? $e->getCode()
                : 500;

            return response()->json(['message' => $e->getMessage()], $code);
        }
    }

    public function destroy(Request $request, $id)
    {
        $this->authorizeAdmin();

        $product = Product::find($id);
        if (!$product) {
            $trashed = Product::onlyTrashed()->find($id);
            if ($trashed) {
                return response()->json(['message' => __('messages.data_conflict')], 409);
            }
            return response()->json(['message' => 'Sản phẩm không tồn tại.'], 404);
        }

        try {
            $version = $request->input('updated_at');
            $this->productService->deleteProduct($product, $version);
            return response()->json(['message' => 'Đã chuyển vào thùng rác.']);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599
                ? $e->getCode()
                : 500;
            return response()->json(['message' => $e->getMessage()], $code);
        }
    }

    /**
     * [Đặng Văn Hà - 4.3.14] Thùng rác sản phẩm (Soft Delete)
     */
    public function trash()
    {
        $this->authorizeAdmin();
        // Chỉ lấy các sản phẩm đã xóa mềm.
        return response()->json($this->productService->getTrashed());
    }

    public function restore($id)
    {
        $this->authorizeAdmin();
        // Khôi phục sản phẩm từ thùng rác về danh sách chính.
        $product = $this->productService->restoreProduct($id);
        return response()->json(['message' => 'Khôi phục thành công.', 'product' => $product]);
    }

    public function forceDelete($id)
    {
        $this->authorizeAdmin();
        $this->productService->forceDeleteProduct($id);
        return response()->json(['message' => 'Đã xóa vĩnh viễn sản phẩm.']);
    }

    /**
     * Upload nhiều ảnh chi tiết
     */
    public function uploadImages(Request $request, Product $product)
    {
        $this->authorizeAdmin();
        // images[] phải là nhiều file ảnh, mỗi file tối đa 2MB.
        $request->validate([
            'images'   => 'required|array',
            'images.*' => 'image|max:2048'
        ]);

        $images = $this->productService->uploadProductImages($product, $request->file('images'));
        return response()->json(['message' => 'Upload ảnh thành công!', 'images' => $images]);
    }

    /**
     * Xóa ảnh chi tiết
     */
    public function deleteImage($id, $imageId)
    {
        $this->authorizeAdmin();
        $this->productService->deleteImage($imageId);
        return response()->json(['message' => 'Đã xóa ảnh chi tiết.']);
    }

    /**
     * Upload file chung
     */
    public function uploadFile(Request $request)
    {
        $this->authorizeAdmin();
        $request->validate([
            'file' => 'required|file|max:5120|mimes:jpg,jpeg,png,webp,pdf,doc,docx'
        ]);
        
        $path = $this->productService->uploadFile($request->file('file'));
        return response()->json(['url' => asset("storage/$path"), 'path' => $path]);
    }

    public function checkUpdated(Request $request, $id)
    {
        // Frontend polling endpoint này để biết sản phẩm có bị admin khác sửa chưa.
        $lastTime = $request->query('last_time', now()->subMinute());
        return response()->json($this->productService->checkUpdated($id, $lastTime));
    }

    private function authorizeAdmin()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Yêu cầu quyền quản trị.');
        }
    }
}
