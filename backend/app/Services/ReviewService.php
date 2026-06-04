<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Order;
use Exception;

class ReviewService
{
    private const PURCHASE_REQUIRED_MESSAGE = 'Bạn cần mua sản phẩm này để có thể đánh giá';

    // ===================== METHODS DI CHUYỂN TỪ CONTROLLER =====================

    /**
     * Lấy danh sách đánh giá đã duyệt của một sản phẩm (public).
     * Di chuyển từ ReviewController@index để loại bỏ truy vấn Model trực tiếp trong Controller.
     */
    public function getProductReviews(int $productId, int $perPage): \Illuminate\Contracts\Pagination\Paginator
    {
        return Review::query()
            ->where('product_id', $productId)
            ->where('status', 'approved')
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->simplePaginate($perPage);
    }

    /**
     * Cập nhật nội dung đánh giá (chỉ chủ đánh giá).
     * Tích hợp kiểm tra quyền sở hữu từ Controller.
     * Throws Exception 403 nếu không phải chủ đánh giá.
     */
    public function updateReview(Review $review, array $data, int $userId): Review
    {
        if ($userId !== $review->user_id) {
            throw new Exception('Bạn không có quyền sửa đánh giá này.', 403);
        }
        $review->update($data);
        $review->load('user:id,name');
        return $review;
    }

    // ===================== METHODS CŨ =====================

    /**
     * [Đặng Văn Hà - 4.3.16] Thêm đánh giá (Check khách đã mua + Tính AVG Sao)
     */
    public function createReview(int $userId, array $data): Review
    {
        // Đơn phải thuộc user và có chứa sản phẩm này.
        $order = Order::where('id', $data['order_id'])
            ->where('user_id', $userId)
            ->whereHas('items', fn($q) => $q->where('product_id', $data['product_id']))
            ->first();

        if (!$order) {
            throw new Exception(self::PURCHASE_REQUIRED_MESSAGE, 403);
        }

        if ($order->status !== 'completed') {
            throw new Exception(self::PURCHASE_REQUIRED_MESSAGE, 403);
        }

        // Mỗi user chỉ được đánh giá một lần cho một sản phẩm.
        $exists = Review::where('user_id', $userId)->where('product_id', $data['product_id'])->exists();
        if ($exists) {
            throw new Exception(__('messages.review_exists'), 422);
        }

        $review = Review::create(array_merge($data, [
            'user_id'    => $userId,
            'created_at' => now(),
        ]));

        // Sau khi thêm review, cập nhật lại điểm sao trung bình của sản phẩm.
        $review->product->recalcAvgRating();

        return $review;
    }

    /**
     * Xóa đánh giá (Admin hoặc chính chủ)
     */
    public function deleteReview(Review $review, $user): bool
    {
        if (!$user) {
            throw new Exception(__('messages.login_required'), 401);
        }
        if (!$user->isAdmin() && $review->user_id !== $user->id) {
            throw new Exception(__('messages.unauthorized'), 403);
        }

        $product  = $review->product;
        $reviewId = $review->id;
        Review::destroy($reviewId);

        // Xóa review xong phải tính lại avg_rating.
        if ($product) {
            $product->recalcAvgRating();
        }

        return true;
    }

    /**
     * Lấy tất cả đánh giá cho Admin (lọc theo rating/status)
     */
    public function getAllAdmin($rating = null, $status = null)
    {
        $query = Review::with(['user:id,name', 'product:id,name'])
            ->orderBy('created_at', 'desc');

        if ($rating) {
            $query->where('rating', $rating);
        }
        if (in_array($status, ['approved', 'hidden'], true)) {
            $query->where('status', $status);
        }

        return $query->paginate(10);
    }

    /**
     * Duyệt/Ẩn đánh giá
     */
    public function moderate(Review $review, string $status): Review
    {
        $review->update(['status' => $status]);

        // Ẩn/duyệt review cũng ảnh hưởng điểm sao trung bình.
        $review->product->recalcAvgRating();

        return $review;
    }
}
