<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Đánh giá & Bình luận sản phẩm phía Khách hàng (Phụ trách: Nguyễn Minh Tự - 20232059)
 */
class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService)
    {
    }

    /** Trang "Tất cả đánh giá" của 1 sản phẩm: /products/{slug}/reviews */
    public function index(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);
        $product->load(['brand', 'category']);

        return view('reviews.index', compact('product'));
    }

    /** Gửi mới / cập nhật đánh giá (mỗi khách 1 đánh giá cho 1 sản phẩm) */
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'title'   => ['nullable', 'string', 'max:180'],
            'pros'    => ['nullable', 'string', 'max:255'],
            'cons'    => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'rating.required'  => 'Vui lòng chọn số sao.',
            'comment.required' => 'Vui lòng nhập nội dung nhận xét.',
            'comment.min'      => 'Nội dung nhận xét tối thiểu 5 ký tự.',
        ]);

        $verified = $this->reviewService->saveReview($product, Auth::id(), $data);

        return back()->with('success', 'Cảm ơn bạn đã gửi đánh giá!' . ($verified ? ' (Đã gắn huy hiệu "Đã mua hàng")' : ''));
    }

    /** Khách tự xóa đánh giá của mình, hoặc Staff/Admin xóa */
    public function destroy(Review $review)
    {
        abort_unless($review->user_id === Auth::id() || Auth::user()->isStaffOrAdmin(), 403);
        $review->delete();

        return back()->with('success', 'Đã xóa đánh giá.');
    }
}
