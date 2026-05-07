<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'price', 'hinh_anh', 'category_id',
        'description', 'stock', 'avg_rating', 'is_active', 'is_featured',
    ];

    /**
     * Cast: price được lưu decimal(12,2) theo ERD
     */
    protected $casts = [
        'price'      => 'decimal:2',
        'avg_rating' => 'decimal:1',
        'is_active'  => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Tính lại avg_rating và lưu vào cột
    public function recalcAvgRating(): void
    {
        $avg = $this->reviews()->where('status', 'approved')->avg('rating');
        $this->update(['avg_rating' => $avg ? round($avg, 1) : null]);
    }

    /**
     * Local Scope: Filter products (Best Practice)
     */
    public function scopeFilter($query, array $filters)
    {
        // Mặc định chỉ lấy sản phẩm đang kinh doanh (is_active = true)
        // Trừ khi admin đang xem (có thể thêm flag admin nếu cần)
        if (!isset($filters['show_all'])) {
            $query->where('is_active', true);
            // Lọc luôn các sản phẩm thuộc danh mục đang bị ẩn
            $query->whereHas('category', function($q) {
                $q->where('is_active', true);
            });
        }
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', true);
        }
        $query->when($filters['search'] ?? null, function ($q, $search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        });

        $categoryId = $filters['category_id'] ?? $filters['category'] ?? null;
        $query->when($categoryId, function ($q, $catId) {
            // Lấy danh sách ID đệ quy để đảm bảo lọc Cha là ra hết Con/Cháu
            $allIds = $this->getAllCategoryIds($catId);
            $q->whereIn('category_id', $allIds);
        });

        $priceFrom = $filters['price_from'] ?? $filters['gia_tu'] ?? null;
        $priceTo   = $filters['price_to'] ?? $filters['gia_den'] ?? null;

        if ($priceFrom !== null) $query->where('price', '>=', $priceFrom);
        if ($priceTo !== null)   $query->where('price', '<=', $priceTo);

        $sortBy  = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';
        
        $allowedSort = ['price', 'created_at', 'avg_rating', 'name'];
        if (in_array($sortBy, $allowedSort)) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    /**
     * Lấy danh sách ID danh mục đệ quy (Hỗ trợ lọc cha con nhiều cấp)
     */
    private function getAllCategoryIds($catId): array
    {
        $ids = [(int)$catId];
        $children = \App\Models\Category::where('parent_id', $catId)->pluck('id')->toArray();
        
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getAllCategoryIds($childId));
        }
        
        return array_unique($ids);
    }

   /**
     * Lấy danh sách sản phẩm rút gọn cho AI (Sửa lỗi crash khi query)
     */
    public static function getAiContext(): string
    {
        // Chỉ lấy sản phẩm đang hoạt động và không bị xóa
        $products = self::where('is_active', true)
            ->get(['id', 'name', 'price', 'stock']);

        if ($products->isEmpty()) {
            return "Hiện tại hệ thống chưa có sản phẩm nào.";
        }

        return "Products(ID|Name|Price|Stock): " . $products->map(function($p) {
            // Ép kiểu price về float để number_format không bị lỗi
            $price = (float)$p->price;
            $formattedPrice = number_format($price, 0, ',', '.') . " VNĐ";
            return "{$p->id}|{$p->name}|{$formattedPrice}|{$p->stock}";
        })->implode('; ');
    }
}