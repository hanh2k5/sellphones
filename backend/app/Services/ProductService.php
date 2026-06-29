<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
        return DB::transaction(function () use ($data) {
            if (isset($data['hinh_anh_file'])) {
                // Lưu ảnh vào thư mục storage/app/public/products
                if (env('PUBLIC_STORAGE_DRIVER') === 'cloudinary') {
                    $data['hinh_anh'] = \App\Services\CloudinaryService::upload($data['hinh_anh_file'], 'products');
                } else {
                    $data['hinh_anh'] = $data['hinh_anh_file']->store('products', 'public');
                }
            }
            return Product::create($data);
        });
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
        return DB::transaction(function () use ($product, $data) {
            if (isset($data['updated_at'])) {
                // So sánh updated_at của client và DB để phát hiện sửa cùng lúc ở 2 tab.
                $clientUpdatedAt = Carbon::parse($data['updated_at'])->utc()->format('Y-m-d\TH:i:s.u\Z');
                $serverUpdatedAt = $product->updated_at->copy()->utc()->format('Y-m-d\TH:i:s.u\Z');

                if ($serverUpdatedAt !== $clientUpdatedAt) {
                    throw new Exception(__('messages.data_conflict'), 409);
                }

                unset($data['updated_at']);
            }

            $oldImage = null;
            // Xử lý thay thế ảnh cũ nếu có upload ảnh mới
            if (isset($data['hinh_anh_file'])) {
                $oldImage = $product->hinh_anh;
                if (env('PUBLIC_STORAGE_DRIVER') === 'cloudinary') {
                    $data['hinh_anh'] = \App\Services\CloudinaryService::upload($data['hinh_anh_file'], 'products');
                } else {
                    $data['hinh_anh'] = $data['hinh_anh_file']->store('products', 'public');
                }
            } elseif (isset($data['hinh_anh']) && $data['hinh_anh'] !== $product->hinh_anh) {
                $oldImage = $product->hinh_anh;
            }
            
            $product->update($data);

            if ($oldImage) {
                // Dọn dẹp ảnh cũ trong Storage ngay sau khi DB cập nhật thành công
                if (env('PUBLIC_STORAGE_DRIVER') === 'cloudinary') {
                    \App\Services\CloudinaryService::delete($oldImage);
                } else {
                    Storage::delete($oldImage);
                    Storage::disk('public')->delete($oldImage);
                }
            }

            return $product;
        });
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
        return DB::transaction(function () use ($product, $version) {
            if ($version) {
                $clientUpdatedAt = Carbon::parse($version)->utc()->format('Y-m-d\TH:i:s.u\Z');
                $serverUpdatedAt = $product->updated_at->copy()->utc()->format('Y-m-d\TH:i:s.u\Z');

                if ($serverUpdatedAt !== $clientUpdatedAt) {
                    throw new Exception('Cảnh báo: Dữ liệu đã thay đổi, vui lòng làm mới!', 409);
                }
            }

            // Kiểm tra nếu sản phẩm đang nằm trong đơn hàng trạng thái "pending" (Chờ xử lý)
            $hasPendingOrder = $product->orderItems()->whereHas('order', function($q) {
                $q->where('status', 'pending');
            })->exists();

            if ($hasPendingOrder) {
                throw new Exception('Không thể xóa sản phẩm này vì đang nằm trong đơn hàng chờ xử lý.', 422);
            }

            return $product->delete();
        });
    }

    /**
     * Lấy danh sách sản phẩm trong Thùng rác.
     */
    public function getTrashed()
    {
        // onlyTrashed() chỉ lấy sản phẩm đang nằm trong thùng rác.
        return Product::onlyTrashed()->with('category')->get();
    }

    public function restoreProduct(Product $product, $version = null)
    {
        return DB::transaction(function () use ($product, $version) {
            if ($version) {
                $clientUpdatedAt = Carbon::parse($version)->utc()->format('Y-m-d\TH:i:s.u\Z');
                $serverUpdatedAt = $product->updated_at->copy()->utc()->format('Y-m-d\TH:i:s.u\Z');

                if ($serverUpdatedAt !== $clientUpdatedAt) {
                    throw new Exception('Cảnh báo: Dữ liệu đã thay đổi, vui lòng làm mới!', 409);
                }
            }

            // [Đặng Văn Hà] Kiểm tra nếu danh mục cha bị xóa/ẩn, tự động gán vào danh mục 'Chưa phân loại'
            $categoryId = $product->category_id;
            $category = $categoryId ? \App\Models\Category::find($categoryId) : null;
            $needsUncategorized = false;

            if (!$category) {
                $needsUncategorized = true;
            } else {
                $currentCat = $category;
                while ($currentCat) {
                    if (!$currentCat->is_active) {
                        $needsUncategorized = true;
                        break;
                    }
                    if ($currentCat->parent_id) {
                        $parentCat = \App\Models\Category::find($currentCat->parent_id);
                        if (!$parentCat) {
                            $needsUncategorized = true;
                            break;
                        }
                        $currentCat = $parentCat;
                    } else {
                        break;
                    }
                }
            }

            if ($needsUncategorized) {
                $uncategorized = \App\Models\Category::firstOrCreate(
                    ['name' => 'Chưa phân loại'],
                    ['is_active' => true]
                );
                $product->category_id = $uncategorized->id;
            }

            $product->restore();
            return $product;
        });
    }

    /**
     * Xóa vĩnh viễn sản phẩm và các tài nguyên ảnh đi kèm.
     */
    public function forceDeleteProduct(Product $product)
    {
        return DB::transaction(function () use ($product) {
            // Ràng buộc: Tuyệt đối không xóa vĩnh viễn nếu sản phẩm đã phát sinh giao dịch (order_items)
            if ($product->orderItems()->exists()) {
                throw new Exception('Không thể xóa vĩnh viễn sản phẩm đã phát sinh giao dịch.', 400);
            }

            // Xóa ảnh đại diện vật lý
            if ($product->hinh_anh) {
                if (env('PUBLIC_STORAGE_DRIVER') === 'cloudinary') {
                    \App\Services\CloudinaryService::delete($product->hinh_anh);
                } else {
                    Storage::disk('public')->delete($product->hinh_anh);
                }
            }

            // Xóa toàn bộ ảnh chi tiết (Gallery) vật lý và bản ghi trong bảng product_images
            foreach ($product->images as $image) {
                if (env('PUBLIC_STORAGE_DRIVER') === 'cloudinary') {
                    \App\Services\CloudinaryService::delete($image->image_path);
                } else {
                    Storage::disk('public')->delete($image->image_path);
                }
                $image->delete();
            }
            
            // Xóa thư mục chứa ảnh chi tiết nếu có
            if (env('PUBLIC_STORAGE_DRIVER') !== 'cloudinary') {
                Storage::disk('public')->deleteDirectory("products/{$product->id}");
            }
            
            $product->forceDelete();
        });
    }

    /**
     * Upload nhiều ảnh chi tiết (Gallery) cho sản phẩm.
     */
    /**
     * [Đặng Văn Hà - 4.3.15] Quản lý bộ sưu tập hình ảnh (Gallery)
     */
    public function uploadProductImages(Product $product, array $files)
    {
        // [Đặng Văn Hà] Bọc trong DB Transaction: nếu 1 ảnh lỗi DB, toàn bộ phiên upload bị rollback.
        return DB::transaction(function () use ($product, $files) {
            $images = [];
            $storedPaths = []; // Ghi lại đường dẫn đã lưu để dọn dẹp nếu rollback
            try {
                foreach ($files as $file) {
                    // Laravel tự băm (hash) tên file → đảm bảo duy nhất, không ghi đè ảnh cũ.
                    if (env('PUBLIC_STORAGE_DRIVER') === 'cloudinary') {
                        $path = \App\Services\CloudinaryService::upload($file, "products/{$product->id}");
                    } else {
                        $path = $file->store("products/{$product->id}", 'public');
                    }
                    $storedPaths[] = $path;
                    $images[] = ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path
                    ]);
                }
                return $images;
            } catch (Exception $e) {
                // Xóa file đã upload khỏi disk khi DB lỗi để tránh file orphan.
                foreach ($storedPaths as $path) {
                    if (env('PUBLIC_STORAGE_DRIVER') === 'cloudinary') {
                        \App\Services\CloudinaryService::delete($path);
                    } else {
                        Storage::disk('public')->delete($path);
                    }
                }
                throw $e;
            }
        });
    }

    /**
     * Xóa một ảnh chi tiết cụ thể.
     */
    public function deleteImage($imageId)
    {
        return DB::transaction(function () use ($imageId) {
            $image = ProductImage::findOrFail($imageId);
            $path = $image->image_path;
            $deleted = $image->delete();
            if ($deleted) {
                if (env('PUBLIC_STORAGE_DRIVER') === 'cloudinary') {
                    \App\Services\CloudinaryService::delete($path);
                } else {
                    Storage::delete($path);
                    Storage::disk('public')->delete($path);
                }
            }
            return $deleted;
        });
    }

    /**
     * Upload file dùng chung cho hệ thống.
     */
    public function uploadFile($file, $folder = 'uploads')
    {
        if (env('PUBLIC_STORAGE_DRIVER') === 'cloudinary') {
            return \App\Services\CloudinaryService::upload($file, $folder);
        } else {
            return $file->store($folder, 'public');
        }
    }

    /**
     * Kiểm tra trạng thái cập nhật (dùng cho cơ chế Real-time Polling ở Frontend).
     */
    public function checkUpdated(Product $product, $lastTime)
    {
        return [
            // true nếu DB mới hơn thời điểm frontend đang giữ.
            'updated'    => $product->updated_at->gt($lastTime),
            'product'    => $product,
            'updated_at' => $product->updated_at->toIso8601String(),
        ];
    }
}
