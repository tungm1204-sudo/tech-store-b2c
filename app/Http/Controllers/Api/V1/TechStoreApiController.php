<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Services\AiAdvisorService;
use Illuminate\Http\Request;

/**
 * RESTful JSON API v1 (Phụ trách: Nguyễn Minh Tự - 20232059)
 */
class TechStoreApiController extends Controller
{
    public function products(Request $request)
    {
        $query = Product::with(['category:id,name,slug', 'brand:id,name,slug'])
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->where('is_active', true);

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . trim($request->q) . '%');
        }

        return response()->json([
            'status' => 'success',
            'data'   => $query->orderByDesc('sold_count')->paginate(12),
        ]);
    }

    public function showProduct(string $slug)
    {
        $product = Product::with(['category', 'brand', 'variants', 'approvedReviews.user:id,name'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json(['status' => 'success', 'data' => $product]);
    }

    public function stats()
    {
        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_products'    => Product::where('is_active', true)->count(),
                'total_orders'      => Order::where('status', '!=', 'cancelled')->count(),
                'total_revenue_vnd' => (float) Order::where('status', '!=', 'cancelled')->sum('total_amount'),
                'avg_rating'        => round((float) Review::where('is_approved', true)->avg('rating'), 2),
                'total_reviews'     => Review::where('is_approved', true)->count(),
            ],
        ]);
    }

    public function recommend(Request $request, AiAdvisorService $advisor)
    {
        $results = $advisor->recommend(
            $request->input('category', 'all'),
            (float) $request->input('budget', 35000000),
            $request->input('usage_type', 'all'),
            $request->input('brand')
        );

        return response()->json(['status' => 'success', 'data' => $results]);
    }

    public function trackOrder(string $orderCode)
    {
        $order = Order::with('items')
            ->where('order_code', $orderCode)
            ->first(['id', 'order_code', 'status', 'payment_method', 'payment_status', 'total_amount', 'created_at', 'delivered_at']);

        if (!$order) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy đơn hàng'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $order]);
    }
}
