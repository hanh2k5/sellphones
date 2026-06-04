<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ReviewService;
use App\Http\Resources\ReviewResource;
use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Http\Requests\ModerateReviewRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * SV THỰC HIỆN: ĐẶNG VĂN HÀ
 * MỤC: 4.3.16 → 4.3.18 (ĐÁNH GIÁ SẢN PHẨM)
 * Thin Controller: không có Review::query trực tiếp, không có $review->update() inline,
 * không có permission check trong Controller. Tất cả đã nằm trong ReviewService.
 */
class ReviewController extends Controller
{
    public function __construct(protected ReviewService $reviewService) {}

    /**
     * GET /products/{product}/reviews — ReviewService@getProductReviews xử lý query.
     */
    public function index(Request $request, Product $product)
    {
        $perPage = min(max((int) $request->query('per_page', 5), 1), 10);
        $reviews = $this->reviewService->getProductReviews($product->id, $perPage);
        return ReviewResource::collection($reviews);
    }

    /**
     * [4.3.16] POST /products/{product}/reviews — ReviewService@createReview xử lý business logic.
     */
    public function store(StoreReviewRequest $request, Product $product)
    {
        try {
            $data               = $request->validated();
            $data['product_id'] = $product->id;

            $review = $this->reviewService->createReview($request->user()->id, $data);
            return response()->json([
                'message'    => 'Cảm ơn bạn đã để lại đánh giá!',
                'review'     => $review->load('user:id,name'),
                'avg_rating' => $product->refresh()->avg_rating,
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    /**
     * PUT /reviews/{review} — ReviewService@updateReview kiểm tra quyền sở hữu + cập nhật.
     */
    public function update(UpdateReviewRequest $request, \App\Models\Review $review)
    {
        try {
            $updatedReview = $this->reviewService->updateReview($review, $request->validated(), Auth::id());
            $product       = $updatedReview->product;
            return response()->json([
                'message'    => 'Cập nhật đánh giá thành công!',
                'review'     => $updatedReview,
                'avg_rating' => $product ? $product->refresh()->avg_rating : null,
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 403);
        }
    }

    /**
     * GET /admin/reviews — Lấy tất cả reviews với filter rating/status.
     */
    public function adminIndex(Request $request)
    {
        return response()->json($this->reviewService->getAllAdmin(
            $request->query('rating'),
            $request->query('status')
        ));
    }

    /**
     * POST /admin/reviews/{review}/moderate — Duyệt/Ẩn đánh giá.
     */
    public function moderate(ModerateReviewRequest $request, \App\Models\Review $review)
    {
        $updatedReview = $this->reviewService->moderate($review, $request->status);
        return response()->json([
            'message' => 'Đã cập nhật trạng thái đánh giá!',
            'review'  => $updatedReview->load(['user:id,name', 'product:id,name']),
        ]);
    }

    /**
     * DELETE /reviews/{review} — ReviewService@deleteReview kiểm tra quyền xóa.
     */
    public function destroy(\App\Models\Review $review)
    {
        try {
            $product = $review->product;
            $this->reviewService->deleteReview($review, Auth::user());
            return response()->json([
                'message'    => 'Đã xóa đánh giá thành công.',
                'avg_rating' => $product ? $product->refresh()->avg_rating : null,
            ]);
        } catch (Exception $e) {
            $code = is_numeric($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599
                ? $e->getCode() : 500;
            return response()->json(['message' => $e->getMessage()], $code);
        }
    }
}
