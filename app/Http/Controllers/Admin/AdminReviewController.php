<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Controller Kiểm duyệt Đánh giá, Phản hồi Admin & Thống kê Bình luận (Phụ trách chính: Nguyễn Minh Tự - 20232059)
 */
class AdminReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::with(['product', 'user', 'replier']);

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->rating);
        }
        if ($request->filled('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }
        if ($request->boolean('verified_only')) {
            $query->where('is_verified_purchase', true);
        }
        if ($request->boolean('unreplied_only')) {
            $query->whereNull('admin_reply');
        }
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('comment', 'like', "%{$q}%")
                    ->orWhere('title', 'like', "%{$q}%")
                    ->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"));
            });
        }

        $reviews = $query->orderByDesc('updated_at')->orderByDesc('id')->paginate(12)->withQueryString();

        $summary = [
            'total'          => Review::count(),
            'avg_rating'     => round((float) Review::where('is_approved', true)->avg('rating'), 2),
            'verified_count' => Review::where('is_verified_purchase', true)->count(),
            'pending_reply'  => Review::whereNull('admin_reply')->count(),
            'hidden_count'   => Review::where('is_approved', false)->count(),
        ];

        return view('admin.reviews.index', compact('reviews', 'summary'));
    }

    public function reply(Request $request, Review $review)
    {
        $data = $request->validate([
            'admin_reply' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $review->update([
            'admin_reply' => $data['admin_reply'],
            'replied_at'  => now(),
            'replied_by'  => Auth::id(),
        ]);

        return back()->with('success', "Đã gửi phản hồi chính thức cho đánh giá #{$review->id}.");
    }

    public function toggleApproval(Review $review)
    {
        $review->update(['is_approved' => !$review->is_approved]);
        $state = $review->is_approved ? 'Hiển thị công khai' : 'Tạm ẩn khỏi Storefront';
        return back()->with('success', "Đã chuyển đánh giá #{$review->id} sang trạng thái: {$state}.");
    }

    public function destroy(Review $review)
    {
        $review->delete();
        return back()->with('success', 'Đã xóa đánh giá vĩnh viễn.');
    }
}
