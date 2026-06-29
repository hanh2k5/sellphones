<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use App\Http\Requests\ProductRequest;
use App\Http\Requests\UploadImagesRequest;
use App\Http\Requests\UploadFileRequest;
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
        $total = $products->total();
        return ProductResource::collection($products)->additional([
            'message' => "Tìm thấy {$total} sản phẩm trong tầm giá của bạn"
        ]);
    }

    /**
     * [Đặng Văn Hà - 4.3.9] Hiển thị chi tiết sản phẩm (Eager Loading)
     */
    public function show($id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['message' => 'Sản phẩm không tồn tại hoặc đã bị xóa'], 404);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        // Khách không thấy sản phẩm ngừng bán; admin vẫn được xem để quản lý.
        if (!$product->is_active && (!Auth::check() || !$user->isAdmin())) {
            abort(404);
        }
        // Review được tải riêng bằng endpoint simplePaginate để trang chi tiết không load quá nhiều cùng lúc.
        $product->load(['category', 'images']);
        return (new ProductResource($product))->resolve();
    }

    /**
     * [Đặng Văn Hà - 4.3.5] Thêm mới sản phẩm (Quản lý dữ liệu và Hình ảnh)
     */
    public function store(ProductRequest $request)
    {
        try {
            // validated() chỉ lấy dữ liệu đã qua rule trong ProductRequest.
            $product = $this->productService->createProduct($request->validated());
            return (new ProductResource($product))
                ->additional(['message' => 'Thêm sản phẩm thành công!'])
                ->response()
                ->setStatusCode(201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(ProductRequest $request, Product $product)
    {
        if ($product->trashed()) {
            return response()->json(['message' => __('messages.data_conflict')], 409);
        }

        try {
            // Service kiểm tra updated_at; nếu dữ liệu cũ sẽ trả lỗi 409.
            $updatedProduct = $this->productService->updateProduct($product, $request->validated());
            return (new ProductResource($updatedProduct))
                ->additional(['message' => 'Cập nhật thành công!']);
        } catch (Exception $e) {
            if ($e->getCode() === 409) {
                return response()->json(['message' => $e->getMessage()], 409);
            }
            return response()->json(['message' => 'Lỗi hệ thống, chưa thể cập nhật thông tin'], 500);
        }
    }

    public function destroy(Request $request, Product $product)
    {
        if ($product->trashed()) {
            return response()->json(['message' => 'Cảnh báo: Dữ liệu đã thay đổi, vui lòng làm mới!'], 409);
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
        // Chỉ lấy các sản phẩm đã xóa mềm.
        return ProductResource::collection($this->productService->getTrashed());
    }

    public function restore(Request $request, $id)
    {
        // Resolve thủ công để kiểm soát hoàn toàn message khi product không tồn tại.
        $product = Product::withTrashed()->find($id);
        if (!$product) {
            return response()->json(['message' => 'Sản phẩm không tồn tại hoặc đã bị xóa vĩnh viễn.'], 404);
        }

        if (!$product->trashed()) {
            return response()->json(['message' => 'Sản phẩm này đã được khôi phục bởi một Admin khác.'], 409);
        }

        try {
            // Khôi phục sản phẩm từ thùng rác về danh sách chính.
            $version = $request->input('updated_at');
            $restoredProduct = $this->productService->restoreProduct($product, $version);
            return response()->json(['message' => 'Khôi phục thành công.', 'product' => $restoredProduct]);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599
                ? $e->getCode()
                : 500;
            return response()->json(['message' => $e->getMessage()], $code);
        }
    }

    public function forceDelete($id)
    {
        // Resolve thủ công để kiểm soát hoàn toàn message khi product không tồn tại.
        $product = Product::withTrashed()->find($id);
        if (!$product) {
            return response()->json(['message' => 'Sản phẩm không tồn tại hoặc đã bị xóa vĩnh viễn.'], 404);
        }

        if (!$product->trashed()) {
            return response()->json(['message' => 'Sản phẩm này đã được khôi phục bởi một Admin khác.'], 409);
        }

        try {
            $this->productService->forceDeleteProduct($product);
            return response()->json(['message' => 'Đã xóa vĩnh viễn sản phẩm.']);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599
                ? $e->getCode()
                : 500;
            return response()->json(['message' => $e->getMessage()], $code);
        }
    }

    /**
     * Upload nhiều ảnh chi tiết
     */
    public function uploadImages(UploadImagesRequest $request, Product $product)
    {
        $images = $this->productService->uploadProductImages($product, $request->file('images'));
        return response()->json(['message' => 'Upload ảnh thành công!', 'images' => $images]);
    }

    /**
     * Xóa ảnh chi tiết
     */
    public function deleteImage(Product $product, $imageId)
    {
        $this->productService->deleteImage($imageId);
        return response()->json(['message' => 'Đã xóa ảnh chi tiết.']);
    }

    /**
     * Upload file chung
     */
    public function uploadFile(UploadFileRequest $request)
    {
        $path = $this->productService->uploadFile($request->file('file'));
        $url = str_starts_with($path, 'http') ? $path : \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        return response()->json(['url' => $url, 'path' => $path]);
    }

    public function checkUpdated(Request $request, Product $product)
    {
        // Frontend polling endpoint này để biết sản phẩm có bị admin khác sửa chưa.
        $lastTime = $request->query('last_time', now()->subMinute());
        return response()->json($this->productService->checkUpdated($product, $lastTime));
    }

}
