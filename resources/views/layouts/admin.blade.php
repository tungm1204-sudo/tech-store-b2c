<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Portal - TechStore B2C')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f1f5f9; font-family: 'Segoe UI', system-ui, sans-serif; }
        .admin-sidebar {
            width: 265px;
            min-height: 100vh;
            background: #0f172a;
            color: #e2e8f0;
        }
        .admin-sidebar .nav-link {
            color: #cbd5e1;
            border-radius: 10px;
            padding: 9px 14px;
            margin-bottom: 3px;
            font-size: 0.92rem;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            background: #2563eb;
            color: #fff;
        }
        .kpi-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
        }
    </style>
    @stack('styles')
</head>
<body>
<div class="d-flex">
    <aside class="admin-sidebar p-3 d-none d-lg-flex flex-column shrink-0">
        <a href="{{ ts_route('admin.dashboard') }}" class="text-white text-decoration-none d-flex align-items-center gap-2 mb-3 pb-3 border-bottom border-secondary">
            <span class="bg-primary rounded-3 px-2 py-1"><i class="bi bi-shield-lock-fill"></i></span>
            <div>
                <div class="fw-bold">TechStore Admin</div>
                <small class="text-info text-uppercase">{{ auth()->user()->role }}</small>
            </div>
        </a>

        <div class="text-uppercase small text-secondary fw-bold mb-1 px-2">Báo cáo & Đánh giá (Tự)</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item"><a href="{{ ts_route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><i class="bi bi-bar-chart-line me-2"></i>Thống kê & Doanh thu</a></li>
            <li class="nav-item"><a href="{{ ts_route('admin.reviews.index') }}" class="nav-link {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"><i class="bi bi-chat-square-quote me-2"></i>Đánh giá & Bình luận</a></li>
        </ul>

        <div class="text-uppercase small text-secondary fw-bold mb-1 px-2">Vận hành & Kho (Trung Anh)</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item"><a href="{{ ts_route('admin.orders.index') }}" class="nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}"><i class="bi bi-receipt-cutoff me-2"></i>Quản lý Đơn hàng</a></li>
            <li class="nav-item"><a href="{{ ts_route('admin.warranties.index') }}" class="nav-link {{ request()->routeIs('admin.warranties.*') ? 'active' : '' }}"><i class="bi bi-tools me-2"></i>Đổi trả & Bảo hành RMA</a></li>
            <li class="nav-item"><a href="{{ ts_route('admin.stock_receipts.index') }}" class="nav-link {{ request()->routeIs('admin.stock_receipts.*') ? 'active' : '' }}"><i class="bi bi-box-seam me-2"></i>Phiếu Nhập kho</a></li>
        </ul>

        @if(auth()->user()->isAdmin())
            <div class="text-uppercase small text-secondary fw-bold mb-1 px-2">Quản trị Cốt lõi (Admin)</div>
            <ul class="nav flex-column mb-3">
                <li class="nav-item"><a href="{{ ts_route('admin.products.index') }}" class="nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"><i class="bi bi-laptop me-2"></i>Sản phẩm & Specs (Tuyến)</a></li>
                <li class="nav-item"><a href="{{ ts_route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"><i class="bi bi-tags me-2"></i>Danh mục (Tuyến)</a></li>
                <li class="nav-item"><a href="{{ ts_route('admin.brands.index') }}" class="nav-link {{ request()->routeIs('admin.brands.*') ? 'active' : '' }}"><i class="bi bi-building me-2"></i>Thương hiệu (Tuyến)</a></li>
                <li class="nav-item"><a href="{{ ts_route('admin.coupons.index') }}" class="nav-link {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}"><i class="bi bi-ticket-perforated me-2"></i>Mã giảm giá (Phúc)</a></li>
                <li class="nav-item"><a href="{{ ts_route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"><i class="bi bi-people me-2"></i>Tài khoản & RBAC (Điền)</a></li>
            </ul>
        @endif

        <div class="mt-auto pt-3 border-top border-secondary">
            <a href="{{ ts_route('home') }}" class="btn btn-outline-info btn-sm w-100 mb-2"><i class="bi bi-shop me-1"></i>Xem Storefront</a>
            <form action="{{ ts_route(['logout', 'dev.logout']) }}" method="POST">
                @csrf
                <button class="btn btn-outline-danger btn-sm w-100" type="submit"><i class="bi bi-box-arrow-right me-1"></i>Đăng xuất</button>
            </form>
        </div>
    </aside>

    <div class="flex-grow-1 d-flex flex-column min-vh-100">
        <header class="bg-white border-bottom px-4 py-3 d-flex justify-content-between align-items-center shadow-sm">
            <div class="fw-bold fs-5 text-dark">@yield('page_title', 'Bảng điều khiển Quản trị')</div>
            <div class="d-flex align-items-center gap-3">
                <span class="small text-muted d-none d-sm-inline">Xin chào, <strong>{{ auth()->user()->name }}</strong></span>
                <a href="{{ ts_route('home') }}" class="btn btn-sm btn-outline-primary d-lg-none">Storefront</a>
            </div>
        </header>

        <main class="p-4 flex-grow-1">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul></div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
@stack('scripts')
</body>
</html>
