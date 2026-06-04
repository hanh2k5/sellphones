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
        // Đơn phải thuộc user, đã hoàn thành và có chứa sản phẩm này.
        $hasBought = Order::where('id', $data['order_id'])
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->whereHas('items', fn($q) => $q->where('product_id', $data['product_id']))
            ->exists();

        if (!$hasBought) {
            throw new Exception(__('messages.order_invalid_or_not_purchased'), 403);
        }

        // Mỗi user chỉ được đánh giá một lần cho một sản phẩm.
        $exists = Review::where('user_id', $userId)->where('product_id', $data['product_id'])->exists();
        if ($exists) {
            throw new Exception(__('messages.review_exists'), 422);
        }

        $review = Review::create(array_merge($data, [
            'user_id' => $userId,
            'created_at' => now()
        ]));
        
        // Sau khi thêm review, cập nhật lại điểm sao trung bình của sản phẩm.
        $review->product->recalcAvgRating();

        return $review;
    }

    /**
     * Xóa đánh giá (Admin hoặc chính chủ)
     */
    public function deleteReview(Review $review, $user)
    {
        // Bắt buộc đăng nhập trước khi xóa đánh giá.
        if (!$user) {
            throw new Exception(__('messages.login_required'), 401);
        }

        // User thường chỉ xóa review của mình, admin được xóa mọi review.
        if (!$user->isAdmin() && $review->user_id !== $user->id) {
            throw new Exception(__('messages.unauthorized'), 403);
        }

        $product = $review->product;
        $review->delete();

        // Xóa review xong phải tính lại avg_rating.
        if ($product) {
            $product->recalcAvgRating();
        }
        
        return true;
    }

    /**
     * Lấy tất cả đánh giá cho Admin
     */
    public function getAllAdmin($rating = null)
    {
        $query = Review::with(['user:id,name', 'product:id,name'])
            ->orderBy('created_at', 'desc');
            
        if ($rating) {
            $query->where('rating', $rating);
        }
            
        return $query->paginate(10);
    }

    /**
     * Duyệt/Ẩn đánh giá
     */
    public function moderate(Review $review, $status)
    {
        $review->update(['status' => $status]);
        
        // Ẩn/duyệt review cũng ảnh hưởng điểm sao trung bình.
        $review->product->recalcAvgRating();

        return $review;
    }
}
