<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Exception;

/**
 * Service quản lý toàn bộ nghiệp vụ liên quan đến Sản phẩm.
 */
class ProductService
{
    /**
     * Lấy danh sách sản phẩm có lọc và phân trang.
     */
    /**
     * [Đặng Văn Hà - 4.3.8] Lấy danh sách sản phẩm (Filter & Phân trang)
     */
    public function getAllProducts(array $params)
    {
        return Product::filter($params)
            ->with('category')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    /**
     * Tạo sản phẩm mới kèm xử lý upload ảnh đại diện.
     */
    /**
     * [Đặng Văn Hà - 4.3.5] Tạo sản phẩm mới (Xử lý dữ liệu và File Upload)
     */
    public function createProduct(array $data)
    {
        if (isset($data['hinh_anh_file'])) {
            // Lưu ảnh vào thư mục storage/app/public/products
            $data['hinh_anh'] = $data['hinh_anh_file']->store('products', 'public');
        }
        return Product::create($data);
    }

    /**
     * Cập nhật thông tin sản phẩm.
     * Áp dụng kỹ thuật Optimistic Locking để chống ghi đè dữ liệu.
     */
    /**
     * [Đặng Văn Hà - 4.3.6] Cập nhật thông tin sản phẩm
     * [Đặng Văn Hà - 4.3.2] Xử lý tranh chấp dữ liệu (Optimistic Locking)
     */
    public function updateProduct(Product $product, array $data)
    {
        /**
         * Kiểm tra tranh chấp dữ liệu (Optimistic Locking):
         * Nếu thời gian cập nhật cuối cùng ở Client khác ở Server -> Đã có Admin khác vừa sửa xong.
         */
        if (isset($data['updated_at']) && $product->updated_at->toIso8601String() !== $data['updated_at']) {
            throw new Exception('Sản phẩm đã bị thay đổi bởi người khác. Vui lòng tải lại trang để tránh mất dữ liệu.', 409);
        }

        // Xử lý thay thế ảnh cũ nếu có upload ảnh mới
        if (isset($data['hinh_anh_file'])) {
            if ($product->hinh_anh) {
                Storage::disk('public')->delete($product->hinh_anh);
            }
            $data['hinh_anh'] = $data['hinh_anh_file']->store('products', 'public');
        }
        
        $product->update($data);
        return $product;
    }

    /**
     * Xóa sản phẩm (Sử dụng Soft Delete).
     * Sản phẩm sẽ được chuyển vào thùng rác thay vì xóa vĩnh viễn.
     */
    /**
     * [Đặng Văn Hà - 4.3.7] Xóa sản phẩm (Soft Delete)
     */
    public function deleteProduct(Product $product, $version = null)
    {
        if ($version && $product->updated_at->toIso8601String() !== $version) {
            throw new Exception('Sản phẩm đã bị thay đổi bởi người khác. Vui lòng tải lại trang.', 409);
        }
        return $product->delete();
    }

    /**
     * Lấy danh sách sản phẩm trong Thùng rác.
     */
    public function getTrashed()
    {
        return Product::onlyTrashed()->with('category')->paginate(10);
    }

    /**
     * Khôi phục sản phẩm từ thùng rác.
     */
    /**
     * [Đặng Văn Hà - 4.3.14] Khôi phục sản phẩm từ thùng rác
     */
    public function restoreProduct($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();
        return $product;
    }

    /**
     * Xóa vĩnh viễn sản phẩm và các tài nguyên ảnh đi kèm.
     */
    public function forceDeleteProduct($id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        
        // Xóa ảnh đại diện vật lý
        if ($product->hinh_anh) {
            Storage::disk('public')->delete($product->hinh_anh);
        }

        // Xóa toàn bộ ảnh chi tiết (Gallery) vật lý
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }
        
        $product->forceDelete();
    }

    /**
     * Upload nhiều ảnh chi tiết (Gallery) cho sản phẩm.
     */
    /**
     * [Đặng Văn Hà - 4.3.15] Quản lý bộ sưu tập hình ảnh (Gallery)
     */
    public function uploadProductImages(Product $product, array $files)
    {
        $images = [];
        foreach ($files as $file) {
            $path = $file->store("products/{$product->id}", 'public');
            $images[] = ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path
            ]);
        }
        return $images;
    }

    /**
     * Xóa một ảnh chi tiết cụ thể.
     */
    public function deleteImage($imageId)
    {
        $image = ProductImage::findOrFail($imageId);
        Storage::disk('public')->delete($image->image_path);
        return $image->delete();
    }

    /**
     * Upload file dùng chung cho hệ thống.
     */
    public function uploadFile($file, $folder = 'uploads')
    {
        return $file->store($folder, 'public');
    }

    /**
     * Kiểm tra trạng thái cập nhật (dùng cho cơ chế Real-time Polling ở Frontend).
     */
    public function checkUpdated($id, $lastTime)
    {
        $product = Product::findOrFail($id);
        return [
            'updated'    => $product->updated_at->gt($lastTime),
            'product'    => $product,
            'updated_at' => $product->updated_at->toIso8601String(),
        ];
    }
}
