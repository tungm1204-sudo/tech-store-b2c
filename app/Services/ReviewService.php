<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Nghiệp vụ Đánh giá & Bình luận sản phẩm (Phụ trách: Nguyễn Minh Tự - 20232059)
 *
 * Tách riêng thành Service để:
 *  - ReviewController (trang đánh giá riêng) và trang chi tiết sản phẩm của Tuyến
 *    dùng chung một nguồn dữ liệu.
 *  - Tuyến chỉ cần 1 dòng: @includeIf('reviews._section', ['product' => $product])
 */
class ReviewService
{
    /** Trạng thái đơn hàng được coi là "đã nhận hàng thành công" */
    public const DELIVERED_STATUS = 'delivered';

    /**
     * Tìm đơn hàng đã giao thành công gần nhất của user có chứa sản phẩm này.
     * Đây là căn cứ gắn huy hiệu "Đã mua hàng" (Verified Purchase).
     */
    public function findDeliveredOrder(?int $userId, Product $product): ?Order
    {
        if (! $userId) {
            return null;
        }

        return Order::where('user_id', $userId)
            ->where('status', self::DELIVERED_STATUS)
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->latest()
            ->first();
    }

    /**
     * Toàn bộ dữ liệu cho khối đánh giá: điểm trung bình, phân bố 5★→1★,
     * danh sách đánh giá đã lọc + phân trang, quyền đánh giá của user hiện tại.
     */
    public function sectionData(Product $product, Request $request): array
    {
        $approved = $product->approvedReviews()->get(['id', 'rating']);
        $totalReviews = $approved->count();
        $avgRating = $totalReviews > 0 ? round($approved->avg('rating'), 1) : 0.0;

        $ratingBreakdown = [];
        for ($star = 5; $star >= 1; $star--) {
            $count = $approved->where('rating', $star)->count();
            $ratingBreakdown[$star] = [
                'count'   => $count,
                'percent' => $totalReviews > 0 ? (int) round($count / $totalReviews * 100) : 0,
            ];
        }

        $query = $product->approvedReviews()->with('user')->latest('updated_at');
        if ($request->filled('star')) {
            $query->where('rating', (int) $request->input('star'));
        }
        if ($request->boolean('verified_only')) {
            $query->where('is_verified_purchase', true);
        }

        $reviews = $query->paginate(6, ['*'], 'review_page')->withQueryString();

        $myReview = Auth::check()
            ? Review::where('product_id', $product->id)->where('user_id', Auth::id())->first()
            : null;

        return [
            'totalReviews'    => $totalReviews,
            'avgRating'       => $avgRating,
            'ratingBreakdown' => $ratingBreakdown,
            'reviews'         => $reviews,
            'hasPurchased'    => $this->findDeliveredOrder(Auth::id(), $product) !== null,
            'myReview'        => $myReview,
        ];
    }

    /**
     * Tạo mới hoặc cập nhật đánh giá (mỗi khách chỉ có 1 đánh giá / sản phẩm).
     * Trả về true nếu đánh giá được gắn huy hiệu Verified Purchase.
     */
    public function saveReview(Product $product, int $userId, array $data): bool
    {
        $deliveredOrder = $this->findDeliveredOrder($userId, $product);

        Review::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => $userId],
            [
                'order_id'             => $deliveredOrder?->id,
                'rating'               => $data['rating'],
                'title'                => $data['title'] ?? null,
                'pros'                 => $data['pros'] ?? null,
                'cons'                 => $data['cons'] ?? null,
                'comment'              => $data['comment'],
                'is_verified_purchase' => $deliveredOrder !== null,
                'is_approved'          => true,
            ]
        );

        return $deliveredOrder !== null;
    }
}
