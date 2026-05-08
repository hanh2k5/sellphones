<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Product;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * SV THỰC HIỆN: ĐẶNG VĂN HÀ
 * MỤC: 4.3.16 -> 4.3.18 (ĐÁNH GIÁ SẢN PHẨM)
 */
class ReviewController extends Controller
{
    protected $reviewService;

    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function index(Product $product)
    {
        $reviews = $product->reviews()
            ->where('status', 'approved')
            ->with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->get();
        return \App\Http\Resources\ReviewResource::collection($reviews);
    }

    /**
     * [Đặng Văn Hà - 4.3.16] Thêm đánh giá sản phẩm (Check khách đã mua)
     */
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'order_id'   => 'required|exists:orders,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'required|string|max:1000',
        ]);

        try {
            $user = Auth::user();
            if (!$user) {
                abort(401, 'Vui lòng đăng nhập.');
            }
            
            // Hợp nhất product_id từ URL vào dữ liệu
            $data = $request->all();
            $data['product_id'] = $product->id;

            $review = $this->reviewService->createReview($user->id, $data);
            return response()->json([
                'message'    => 'Cảm ơn bạn đã để lại đánh giá!',
                'review'     => $review->load('user:id,name'),
                'avg_rating' => $product->refresh()->avg_rating
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    public function update(Request $request, Review $review)
    {
        if (Auth::id() !== $review->user_id) {
            abort(403, 'Bạn không có quyền sửa đánh giá này.');
        }

        $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        $review->update($request->all());
        return response()->json(['message' => 'Đã cập nhật đánh giá.', 'review' => $review]);
    }

    public function adminIndex()
    {
        $this->authorizeAdmin();
        return response()->json($this->reviewService->getAllAdmin());
    }

    public function moderate(Request $request, Review $review)
    {
        $this->authorizeAdmin();
        $request->validate(['status' => 'required|in:approved,hidden']);
        
        $updatedReview = $this->reviewService->moderate($review, $request->status);
        return response()->json([
            'message' => 'Đã cập nhật trạng thái đánh giá!',
            'review'  => $updatedReview->load(['user:id,name', 'product:id,name']),
        ]);
    }

    public function destroy(Review $review)
    {
        try {
            $this->reviewService->deleteReview($review, Auth::user());
            return response()->json(['message' => 'Đã xóa đánh giá thành công.']);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    private function authorizeAdmin()
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Yêu cầu quyền quản trị.');
        }
    }
}
