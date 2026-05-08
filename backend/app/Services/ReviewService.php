<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Order;
use Exception;

class ReviewService
{
    /**
     * [Đặng Văn Hà - 4.3.16] Thêm đánh giá (Check khách đã mua + Tính AVG Sao)
     */
    public function createReview($userId, array $data)
    {
        // Logic: Chỉ cho phép đánh giá nếu đã mua đơn hàng đó và đơn hàng đã xác nhận/hoàn thành
        $hasBought = Order::where('id', $data['order_id'])
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereHas('items', fn($q) => $q->where('product_id', $data['product_id']))
            ->exists();

        if (!$hasBought) {
            throw new Exception('Đơn hàng không hợp lệ hoặc bạn chưa mua sản phẩm này trong đơn hàng này.', 403);
        }

        // Kiểm tra xem đã đánh giá chưa (mỗi người 1 lần/sản phẩm)
        $exists = Review::where('user_id', $userId)->where('product_id', $data['product_id'])->exists();
        if ($exists) {
            throw new Exception('Bạn đã đánh giá sản phẩm này rồi.', 422);
        }

        $review = Review::create(array_merge($data, [
            'user_id' => $userId,
            'created_at' => now()
        ]));
        
        // Tự động tính lại điểm sao cho sản phẩm (Theo báo cáo 4.3.16)
        $review->product->recalcAvgRating();

        return $review;
    }

    /**
     * Xóa đánh giá (Admin hoặc chính chủ)
     */
    public function deleteReview(Review $review, $user)
    {
        // Kiểm tra an toàn người dùng
        if (!$user) {
            throw new Exception('Vui lòng đăng nhập lại.', 401);
        }

        if (!$user->isAdmin() && $review->user_id !== $user->id) {
            throw new Exception('Bạn không có quyền thực hiện hành động này.', 403);
        }

        $product = $review->product;
        $review->delete();

        // Tính lại sao sau khi xóa (kiểm tra product tồn tại)
        if ($product) {
            $product->recalcAvgRating();
        }
        
        return true;
    }

    /**
     * Lấy tất cả đánh giá cho Admin
     */
    public function getAllAdmin()
    {
        return Review::with(['user:id,name', 'product:id,name'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    /**
     * Duyệt/Ẩn đánh giá
     */
    public function moderate(Review $review, $status)
    {
        $review->update(['status' => $status]);
        
        // Tính lại sao sau khi duyệt/ẩn
        $review->product->recalcAvgRating();

        return $review;
    }
}
