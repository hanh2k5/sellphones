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
        'description', 'stock', 'avg_rating', 'is_active', 'is_featured', 'slug',
    ];

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($product) {
            $slug = \Illuminate\Support\Str::slug($product->name);
            $originalSlug = $slug;
            $count = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id ?? 0)->exists()) {
                $slug = $originalSlug . '-' . $count;
                $count++;
            }
            $product->slug = $slug;
        });
    }

    /**
     * Cast: price được lưu decimal(12,2) theo ERD
     */
    protected $casts = [
        'price'      => 'decimal:2',
        'avg_rating' => 'decimal:1',
        'is_active'  => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function getNameAttribute($value)
    {
        return htmlspecialchars(strip_tags($value), ENT_QUOTES, 'UTF-8');
    }

    public function setNameAttribute($value)
    {
        if (is_string($value)) {
            $value = preg_replace('/　/u', ' ', $value);
            $value = trim($value);
        }
        $this->attributes['name'] = $value;
    }

    public function setDescriptionAttribute($value)
    {
        if (is_string($value)) {
            // Loại bỏ hoàn toàn thẻ <script>...</script>
            $cleaned = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $value);
            // Loại bỏ các sự kiện inline (như onload, onerror, onclick, v.v.)
            $cleaned = preg_replace('/(<[^>]+)\b(on[a-z]+)\s*=\s*(["\'])(.*?)\3([^>]*>)/is', '$1$5', $cleaned);
            $cleaned = preg_replace('/on\w+\s*=\s*(["\'])(.*?)\1/is', '', $cleaned);
            // Loại bỏ các javascript: protocol
            $cleaned = preg_replace('/href\s*=\s*(["\'])javascript:[^\1]*?\1/is', '', $cleaned);
            $cleaned = preg_replace('/javascript:[^\s"\']*/is', '', $cleaned);
            $this->attributes['description'] = $cleaned;
        } else {
            $this->attributes['description'] = $value;
        }
    }

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
        // Chỉ lấy review approved để tính điểm hiển thị cho khách.
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
            $query->where('price', '>', 0);
            $query->where('is_active', true);
            // Lọc luôn các sản phẩm thuộc danh mục đang bị ẩn
            $query->whereHas('category', function($q) {
                $q->where('is_active', true);
            });
        }
        if (isset($filters['is_featured'])) {
            $query->where('is_featured', true);
        }
        // [Đặng Văn Hà] Tìm theo tên hoặc mô tả sản phẩm. Lọc ký tự đặc biệt trước khi đưa vào query.
        $query->when($filters['search'] ?? null, function ($q, $search) {
            if (is_array($search)) { $search = implode(' ', $search); }
            // Loại bỏ thẻ HTML và các ký tự nguy hiểm <, >, / để chặn XSS/injection qua ô tìm kiếm.
            $search = strip_tags((string) $search);
            $search = preg_replace('/[<>\/\\\\]/', '', $search);
            $search = trim($search);
            if ($search === '') return;
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

        // Hỗ trợ cả tên tham số tiếng Anh và tiếng Việt từ frontend.
        $priceFrom = $filters['price_from'] ?? $filters['price_min'] ?? $filters['gia_tu'] ?? null;
        $priceTo   = $filters['price_to'] ?? $filters['price_max'] ?? $filters['gia_den'] ?? null;

        if ($priceFrom !== null && $priceTo !== null) {
            $min = (float) $priceFrom;
            $max = (float) $priceTo;
            if ($min > $max) {
                $temp = $min;
                $min = $max;
                $max = $temp;
            }
            $query->whereBetween('price', [$min, $max]);
        } elseif ($priceFrom !== null) {
            $query->where('price', '>=', (float) $priceFrom);
        } elseif ($priceTo !== null) {
            $query->where('price', '<=', (float) $priceTo);
        }

        // Chỉ cho sort theo các cột an toàn đã định nghĩa.
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
