@extends('layouts.admin')

@section('title', 'Thống kê Doanh thu & Biểu đồ - Admin TechStore')
@section('page_title', 'Dashboard Thống Kê, Báo Cáo Doanh Thu & Tồn Kho (Nguyễn Minh Tự)')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <form action="{{ ts_route('admin.dashboard') }}" method="GET" class="d-flex align-items-center flex-wrap gap-2">
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ ts_route('admin.dashboard', ['period' => 'day']) }}" class="btn {{ $period === 'day' ? 'btn-primary' : 'btn-outline-primary' }}">Theo Ngày (14 ngày)</a>
                <a href="{{ ts_route('admin.dashboard', ['period' => 'month', 'year' => $selectedYear]) }}" class="btn {{ $period === 'month' ? 'btn-primary' : 'btn-outline-primary' }}">Theo Tháng</a>
                <a href="{{ ts_route('admin.dashboard', ['period' => 'year']) }}" class="btn {{ $period === 'year' ? 'btn-primary' : 'btn-outline-primary' }}">Theo Năm</a>
            </div>
            <input type="hidden" name="period" value="{{ $period }}">
            <select name="year" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                @foreach($availableYears as $y)
                    <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>Năm {{ $y }}</option>
                @endforeach
            </select>
            <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control form-control-sm" style="width:auto;" title="Từ ngày">
            <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control form-control-sm" style="width:auto;" title="Đến ngày">
            <button type="submit" class="btn btn-sm btn-dark">Lọc</button>
            @if(request('from_date') || request('to_date'))
                <a href="{{ ts_route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            @endif
        </form>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ ts_route('admin.reports.export', ['type' => 'revenue']) }}" class="btn btn-sm btn-success"><i class="bi bi-file-earmark-excel me-1"></i>Xuất CSV Doanh thu</a>
            <a href="{{ ts_route('admin.reports.export', ['type' => 'inventory']) }}" class="btn btn-sm btn-warning text-dark"><i class="bi bi-box-seam me-1"></i>Xuất CSV Tồn kho</a>
            <a href="{{ ts_route('admin.reports.export', ['type' => 'reviews']) }}" class="btn btn-sm btn-info text-dark"><i class="bi bi-star-fill me-1"></i>Xuất CSV Đánh giá</a>
        </div>
    </div>
</div>

<!-- 6 KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3 bg-white border-start border-4 border-primary h-100">
            <div class="small text-muted">Doanh thu (Không tính hủy)</div>
            <div class="fs-5 fw-bold text-primary mt-1">{{ number_format($kpis['total_revenue']) }}₫</div>
            <small class="text-success">Thực thu: {{ number_format($kpis['delivered_revenue']) }}₫</small>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3 bg-white border-start border-4 border-success h-100">
            <div class="small text-muted">Tổng Đơn hàng</div>
            <div class="fs-4 fw-bold text-success mt-1">{{ $kpis['total_orders'] }}</div>
            <small class="text-muted">Hoàn thành: {{ $kpis['delivered_orders'] }} đơn</small>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3 bg-white border-start border-4 border-info h-100">
            <div class="small text-muted">Giá trị Đơn TB (AOV)</div>
            <div class="fs-5 fw-bold text-dark mt-1">{{ number_format($kpis['avg_order_value']) }}₫</div>
            <small class="text-muted">{{ $kpis['total_products'] }} mã sản phẩm</small>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3 bg-white border-start border-4 border-warning h-100">
            <div class="small text-muted">Điểm Đánh giá TB</div>
            <div class="fs-4 fw-bold text-warning mt-1">{{ $kpis['avg_rating'] }} / 5★</div>
            <small class="text-muted">{{ $kpis['total_reviews'] }} bình luận</small>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3 bg-white border-start border-4 border-danger h-100">
            <div class="small text-muted">Cảnh báo Sắp hết kho</div>
            <div class="fs-4 fw-bold text-danger mt-1">{{ $kpis['low_stock_count'] }}</div>
            <small class="text-danger">Tồn kho <= ngưỡng</small>
        </div>
    </div>
    <div class="col-md-4 col-xl-2">
        <div class="card kpi-card p-3 bg-white border-start border-4 border-secondary h-100">
            <div class="small text-muted">RMA / Phiếu nhập chờ</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ $kpis['pending_rma_count'] }} / {{ $kpis['pending_receipts'] }}</div>
            <small class="text-muted">Cần Staff/Admin xử lý</small>
        </div>
    </div>
</div>

<!-- Row Charts 1 -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Biểu đồ 1: Doanh thu (VNĐ) & Số lượng Đơn hàng ({{ strtoupper($period) }})</h6>
            <canvas id="revenueTrendChart" height="125"></canvas>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-success me-2"></i>Biểu đồ 2: Tỷ trọng Doanh thu theo Danh mục</h6>
            <canvas id="categoryShareChart" height="220"></canvas>
        </div>
    </div>
</div>

<!-- Row Charts 2 -->
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i>Biểu đồ 3: Top Sản phẩm Bán chạy nhất</h6>
            <canvas id="topProductsChart" height="150"></canvas>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <h6 class="fw-bold mb-3"><i class="bi bi-star-half text-warning me-2"></i>Biểu đồ 4: Phân bố Trạng thái Đơn & Điểm Đánh giá</h6>
            <div class="row">
                <div class="col-12 mb-3"><canvas id="orderStatusChart" height="120"></canvas></div>
                <div class="col-12"><canvas id="ratingDistChart" height="100"></canvas></div>
            </div>
        </div>
    </div>
</div>

<!-- Low Stock Alert & Recent Orders -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-danger mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>Cảnh báo Sản phẩm Sắp hết hàng</h6>
                <a href="{{ ts_route('admin.stock_receipts.index') }}" class="btn btn-sm btn-outline-danger">Lập Phiếu nhập kho</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>SKU</th><th>Tên thiết bị</th><th>Tồn kho</th><th>Ngưỡng</th></tr></thead>
                    <tbody>
                        @forelse($lowStockProducts as $lp)
                            <tr>
                                <td class="small"><code>{{ $lp->sku }}</code></td>
                                <td class="small fw-semibold">{{ $lp->name }}</td>
                                <td><span class="badge bg-danger">{{ $lp->stock_quantity }}</span></td>
                                <td class="small text-muted">{{ $lp->low_stock_threshold }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-success small">Tất cả sản phẩm đều đủ tồn kho an toàn.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock-history text-primary me-2"></i>Đơn hàng Gần nhất</h6>
                <a href="{{ ts_route('admin.orders.index') }}" class="btn btn-sm btn-outline-primary">Quản lý Đơn</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                        @foreach($recentOrders as $ro)
                            <tr>
                                <td><a href="{{ ts_route('admin.orders.show', $ro) }}" class="fw-bold text-decoration-none">{{ $ro->order_code }}</a></td>
                                <td class="small">{{ $ro->customer_name }}</td>
                                <td class="small fw-bold text-danger">{{ number_format($ro->total_amount) }}₫</td>
                                <td><span class="badge bg-{{ $ro->status_badge }}">{{ $ro->status_label }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const trendLabels   = @json($trendLabels);
const trendRevenues = @json($trendRevenues);
const trendCounts   = @json($trendCounts);
const catLabels     = @json($categoryStats->pluck('name'));
const catValues     = @json($categoryStats->pluck('revenue'));
const topLabels     = @json($topProducts->pluck('name')->map(fn($n) => Str::limit($n, 26)));
const topValues     = @json($topProducts->pluck('sold_count'));
const statusLabels  = @json($orderStatusStats->pluck('label'));
const statusValues  = @json($orderStatusStats->pluck('count'));
const ratingLabels  = @json(collect($ratingStats)->pluck('label'));
const ratingValues  = @json(collect($ratingStats)->pluck('count'));

new Chart(document.getElementById('revenueTrendChart'), {
    type: 'bar',
    data: {
        labels: trendLabels,
        datasets: [
            { label: 'Doanh thu (VNĐ)', data: trendRevenues, backgroundColor: 'rgba(37, 99, 235, 0.75)', borderRadius: 6, yAxisID: 'y' },
            { label: 'Số đơn hàng', data: trendCounts, type: 'line', borderColor: '#f59e0b', backgroundColor: '#f59e0b', tension: 0.3, yAxisID: 'y1' }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, position: 'left' },
            y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
        }
    }
});

new Chart(document.getElementById('categoryShareChart'), {
    type: 'doughnut',
    data: {
        labels: catLabels,
        datasets: [{ data: catValues, backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('topProductsChart'), {
    type: 'bar',
    data: {
        labels: topLabels,
        datasets: [{ label: 'Số lượng đã bán', data: topValues, backgroundColor: '#0ea5e9', borderRadius: 6 }]
    },
    options: { indexAxis: 'y', responsive: true }
});

new Chart(document.getElementById('orderStatusChart'), {
    type: 'bar',
    data: {
        labels: statusLabels,
        datasets: [{ label: 'Số đơn', data: statusValues, backgroundColor: ['#f59e0b','#0dcaf0','#2563eb','#6c757d','#198754','#dc3545'] }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('ratingDistChart'), {
    type: 'bar',
    data: {
        labels: ratingLabels,
        datasets: [{ label: 'Số lượt đánh giá', data: ratingValues, backgroundColor: '#eab308' }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>
@endpush
