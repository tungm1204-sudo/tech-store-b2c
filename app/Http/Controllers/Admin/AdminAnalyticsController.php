<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\StockReceipt;
use App\Models\WarrantyRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller Thống kê, Báo cáo Doanh thu, Tồn kho & Biểu đồ trực quan (Phụ trách chính: Nguyễn Minh Tự - 20232059)
 */
class AdminAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month'); // day | month | year
        if (!in_array($period, ['day', 'month', 'year'], true)) {
            $period = 'month';
        }
        $selectedYear = (int) $request->get('year', now()->year);
        $fromDate = $request->filled('from_date') ? Carbon::parse($request->from_date)->startOfDay() : null;
        $toDate = $request->filled('to_date') ? Carbon::parse($request->to_date)->endOfDay() : null;

        // 1. Tập đơn hàng hợp lệ (không tính đơn đã hủy vào doanh thu)
        $ordersQuery = Order::query();
        if ($fromDate && $toDate) {
            $ordersQuery->whereBetween('created_at', [$fromDate, $toDate]);
        }
        $allOrders = $ordersQuery->get(['id', 'order_code', 'total_amount', 'status', 'payment_status', 'created_at']);
        $revenueOrders = $allOrders->where('status', '!=', 'cancelled');
        $deliveredOrders = $allOrders->where('status', 'delivered');

        $totalRevenue = (float) $revenueOrders->sum('total_amount');
        $deliveredRevenue = (float) $deliveredOrders->sum('total_amount');
        $avgOrderValue = $revenueOrders->count() > 0 ? round($totalRevenue / $revenueOrders->count()) : 0;

        $lowStockProducts = Product::with(['category', 'brand'])
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->get();

        $kpis = [
            'total_revenue'     => $totalRevenue,
            'delivered_revenue' => $deliveredRevenue,
            'total_orders'      => $allOrders->count(),
            'delivered_orders'  => $deliveredOrders->count(),
            'avg_order_value'   => $avgOrderValue,
            'avg_rating'        => round((float) Review::where('is_approved', true)->avg('rating'), 2),
            'total_reviews'     => Review::count(),
            'low_stock_count'   => $lowStockProducts->count(),
            'pending_rma_count' => WarrantyRequest::whereIn('status', ['submitted', 'inspecting'])->count(),
            'pending_receipts'  => StockReceipt::where('status', 'pending')->count(),
            'total_products'    => Product::count(),
        ];

        // 2. Dữ liệu Biểu đồ 1: Doanh thu & Số đơn theo Ngày / Tháng / Năm
        [$trendLabels, $trendRevenues, $trendCounts] = $this->buildTrendSeries($period, $selectedYear, $fromDate, $toDate);

        // 3. Dữ liệu Biểu đồ 2: Tỷ trọng Doanh thu theo Danh mục
        $categoryStats = Category::all()->map(function (Category $cat) {
            $rev = OrderItem::whereHas('product', fn ($q) => $q->where('category_id', $cat->id))
                ->whereHas('order', fn ($q) => $q->where('status', '!=', 'cancelled'))
                ->sum('line_total');
            return ['name' => $cat->name, 'revenue' => (float) $rev];
        });

        // 4. Dữ liệu Biểu đồ 3: Top 8 Sản phẩm bán chạy
        $topProducts = Product::with(['category', 'brand'])
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->orderByDesc('sold_count')
            ->take(8)
            ->get();

        // 5. Dữ liệu Biểu đồ 4: Phân bố Trạng thái Đơn hàng & Phân bố Điểm Đánh giá
        $orderStatusStats = collect(Order::STATUS_LABELS)->map(function ($label, $key) use ($allOrders) {
            return ['label' => $label, 'count' => $allOrders->where('status', $key)->count()];
        })->values();

        $ratingStats = [];
        for ($s = 5; $s >= 1; $s--) {
            $ratingStats[] = [
                'label' => "{$s} Sao",
                'count' => Review::where('rating', $s)->count(),
            ];
        }

        $availableYears = Order::all(['created_at'])
            ->map(fn ($o) => $o->created_at->year)
            ->unique()
            ->sortDesc()
            ->values();
        if ($availableYears->isEmpty()) {
            $availableYears = collect([now()->year]);
        }

        $recentOrders = Order::with('user')->orderByDesc('id')->take(6)->get();

        return view('admin.dashboard', compact(
            'period',
            'selectedYear',
            'availableYears',
            'kpis',
            'trendLabels',
            'trendRevenues',
            'trendCounts',
            'categoryStats',
            'topProducts',
            'lowStockProducts',
            'orderStatusStats',
            'ratingStats',
            'recentOrders'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $type = $request->get('type', 'revenue'); // revenue | inventory | reviews
        $filename = "techstore_report_{$type}_" . now()->format('Ymd_His') . ".csv";

        return response()->streamDownload(function () use ($type) {
            $out = fopen('php://output', 'w');
            // Ghi UTF-8 BOM để Microsoft Excel hiển thị chuẩn tiếng Việt
            fwrite($out, "\xEF\xBB\xBF");

            if ($type === 'inventory') {
                fputcsv($out, ['SKU', 'Tên sản phẩm', 'Danh mục', 'Thương hiệu', 'Giá bán (VND)', 'Tồn kho', 'Ngưỡng cảnh báo', 'Đã bán', 'Trạng thái']);
                Product::with(['category', 'brand'])->orderBy('stock_quantity')->chunk(100, function ($rows) use ($out) {
                    foreach ($rows as $p) {
                        fputcsv($out, [
                            $p->sku,
                            $p->name,
                            $p->category?->name,
                            $p->brand?->name,
                            $p->effective_price,
                            $p->stock_quantity,
                            $p->low_stock_threshold,
                            $p->sold_count,
                            $p->isLowStock() ? 'CẢNH BÁO SẮP HẾT' : 'Ổn định',
                        ]);
                    }
                });
            } elseif ($type === 'reviews') {
                fputcsv($out, ['ID', 'Sản phẩm', 'Khách hàng', 'Số sao', 'Tiêu đề', 'Nội dung', 'Đã mua hàng', 'Phản hồi Admin', 'Ngày tạo']);
                Review::with(['product', 'user'])->orderByDesc('id')->chunk(100, function ($rows) use ($out) {
                    foreach ($rows as $r) {
                        fputcsv($out, [
                            $r->id,
                            $r->product?->name,
                            $r->user?->name,
                            $r->rating,
                            $r->title,
                            $r->comment,
                            $r->is_verified_purchase ? 'Có' : 'Chưa',
                            $r->admin_reply ?? 'Chưa phản hồi',
                            $r->created_at?->format('Y-m-d H:i'),
                        ]);
                    }
                });
            } else {
                fputcsv($out, ['Mã đơn', 'Khách hàng', 'SĐT', 'Tạm tính', 'Giảm giá', 'Phí ship', 'Tổng thanh toán', 'PT Thanh toán', 'TT Thanh toán', 'Trạng thái đơn', 'Ngày đặt']);
                Order::orderByDesc('id')->chunk(100, function ($rows) use ($out) {
                    foreach ($rows as $o) {
                        fputcsv($out, [
                            $o->order_code,
                            $o->customer_name,
                            $o->customer_phone,
                            $o->subtotal,
                            $o->discount_amount,
                            $o->shipping_fee,
                            $o->total_amount,
                            strtoupper($o->payment_method),
                            strtoupper($o->payment_status),
                            $o->status_label,
                            $o->created_at?->format('Y-m-d H:i'),
                        ]);
                    }
                });
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function buildTrendSeries(string $period, int $year, ?Carbon $fromDate, ?Carbon $toDate): array
    {
        $orders = Order::where('status', '!=', 'cancelled')
            ->when($fromDate && $toDate, fn ($q) => $q->whereBetween('created_at', [$fromDate, $toDate]))
            ->get(['total_amount', 'created_at']);

        $labels = [];
        $revenues = [];
        $counts = [];

        if ($period === 'day') {
            $start = $fromDate ? $fromDate->copy() : now()->subDays(13)->startOfDay();
            $end = $toDate ? $toDate->copy() : now()->endOfDay();
            if ($start->diffInDays($end) > 31) {
                $start = $end->copy()->subDays(30);
            }
            $grouped = $orders->groupBy(fn ($o) => $o->created_at->format('Y-m-d'));
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $key = $cursor->format('Y-m-d');
                $bucket = $grouped->get($key, collect());
                $labels[] = $cursor->format('d/m');
                $revenues[] = round((float) $bucket->sum('total_amount'));
                $counts[] = $bucket->count();
                $cursor->addDay();
            }
        } elseif ($period === 'year') {
            $grouped = $orders->groupBy(fn ($o) => (int) $o->created_at->year);
            $years = range(min(2024, now()->year - 2), now()->year);
            foreach ($years as $y) {
                $bucket = $grouped->get($y, collect());
                $labels[] = 'Năm ' . $y;
                $revenues[] = round((float) $bucket->sum('total_amount'));
                $counts[] = $bucket->count();
            }
        } else {
            // month (12 tháng của năm được chọn)
            $yearOrders = $orders->filter(fn ($o) => (int) $o->created_at->year === $year);
            $grouped = $yearOrders->groupBy(fn ($o) => (int) $o->created_at->month);
            for ($m = 1; $m <= 12; $m++) {
                $bucket = $grouped->get($m, collect());
                $labels[] = "Tháng {$m}/{$year}";
                $revenues[] = round((float) $bucket->sum('total_amount'));
                $counts[] = $bucket->count();
            }
        }

        return [$labels, $revenues, $counts];
    }
}
