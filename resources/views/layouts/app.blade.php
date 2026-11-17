<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'TechStore B2C - Cửa hàng Thiết bị Công nghệ Chính hãng')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --ts-navy: #0f172a;
            --ts-blue: #2563eb;
            --ts-accent: #0ea5e9;
            --ts-bg: #f8fafc;
        }
        body {
            background-color: var(--ts-bg);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #1e293b;
        }
        .ts-topbar {
            background: #020617;
            color: #cbd5e1;
            font-size: 0.82rem;
        }
        .ts-navbar {
            background: rgba(15, 23, 42, 0.97);
            backdrop-filter: blur(8px);
        }
        .product-card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            transition: transform .18s ease, box-shadow .18s ease;
            overflow: hidden;
            background: #fff;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            border-color: #93c5fd;
        }
        .spec-pill {
            background: #f1f5f9;
            color: #334155;
            font-size: 0.75rem;
            padding: 3px 8px;
            border-radius: 6px;
            display: inline-block;
            margin: 2px 2px 2px 0;
        }
        .ts-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0ea5e9 100%);
            border-radius: 20px;
            color: #fff;
        }
    </style>
    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    <div class="ts-topbar py-1">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div><i class="bi bi-shield-check text-info me-1"></i> TechStore B2C - Đồ Công Nghệ Chính Hãng • Freeship đơn từ 5.000.000₫ • Bảo hành RMA 12-36 tháng</div>
            <div class="d-flex align-items-center gap-3">
                <span>Ngôn ngữ:</span>
                <a href="{{ ts_route('locale.switch', 'vi') }}" class="text-decoration-none {{ app()->getLocale() === 'vi' ? 'text-info fw-bold' : 'text-light' }}">VI</a>
                <span>/</span>
                <a href="{{ ts_route('locale.switch', 'en') }}" class="text-decoration-none {{ app()->getLocale() === 'en' ? 'text-info fw-bold' : 'text-light' }}">EN</a>
            </div>
        </div>
    </div>

    <nav class="navbar navbar-expand-lg navbar-dark ts-navbar sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ ts_route('home') }}">
                <span class="bg-primary text-white rounded-3 px-2 py-1"><i class="bi bi-cpu-fill"></i></span>
                <span>TechStore <small class="text-info fw-normal">B2C</small></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="{{ ts_route('home') }}">{{ __('shop.home') }}</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="{{ ts_route('products.index') }}" data-bs-toggle="dropdown">{{ __('shop.catalog') }}</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item fw-semibold" href="{{ ts_route('products.index') }}">Tất cả thiết bị</a></li>
                            <li><hr class="dropdown-divider"></li>
                            @foreach($navCategories as $cat)
                                <li><a class="dropdown-item" href="{{ ts_route('products.index', ['category' => $cat->slug]) }}"><i class="bi {{ $cat->icon }} me-2"></i>{{ $cat->name }}</a></li>
                            @endforeach
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ ts_route('compare.index') }}">
                            <i class="bi bi-columns-gap me-1"></i>{{ __('shop.compare') }}
                            @if($compareCount > 0) <span class="badge bg-info text-dark">{{ $compareCount }}</span> @endif
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning fw-semibold" href="{{ ts_route('ai_advisor.index') }}">
                            <i class="bi bi-stars me-1"></i>{{ __('shop.ai_advisor') }}
                        </a>
                    </li>
                </ul>

                <form action="{{ ts_route('products.index') }}" method="GET" class="d-flex me-lg-3 my-2 my-lg-0" role="search">
                    <div class="input-group input-group-sm">
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control bg-dark text-white border-secondary" placeholder="{{ __('shop.search_hint') }}">
                        <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>

                <div class="d-flex align-items-center gap-2">
                    <a href="{{ ts_route('cart.index') }}" class="btn btn-outline-light btn-sm position-relative">
                        <i class="bi bi-cart3 me-1"></i>{{ __('shop.cart') }}
                        @if($cartCount > 0)
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">{{ $cartCount }}</span>
                        @endif
                    </a>

                    @auth
                        <div class="dropdown">
                            <button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i>{{ Str::limit(auth()->user()->name, 16) }}
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li class="dropdown-header small text-muted">Vai trò: <span class="badge bg-secondary text-uppercase">{{ auth()->user()->role }}</span></li>
                                @if(auth()->user()->isStaffOrAdmin())
                                    <li><a class="dropdown-item fw-bold text-primary" href="{{ ts_route('admin.dashboard') }}"><i class="bi bi-speedometer2 me-2"></i>{{ __('shop.admin_panel') }}</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                @endif
                                <li><a class="dropdown-item" href="{{ ts_route('profile.index') }}"><i class="bi bi-person-vcard me-2"></i>Hồ sơ & Sổ địa chỉ</a></li>
                                <li><a class="dropdown-item" href="{{ ts_route('orders.index') }}"><i class="bi bi-bag-check me-2"></i>{{ __('shop.orders') }}</a></li>
                                <li><a class="dropdown-item" href="{{ ts_route('warranties.index') }}"><i class="bi bi-tools me-2"></i>{{ __('shop.warranty') }}</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ ts_route(['logout', 'dev.logout']) }}" method="POST">
                                        @csrf
                                        <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right me-2"></i>{{ __('shop.logout') }}</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        @if(Route::has('login'))
                            <a href="{{ route('login') }}" class="btn btn-outline-info btn-sm">{{ __('shop.login') }}</a>
                            <a href="{{ ts_route('register') }}" class="btn btn-primary btn-sm">{{ __('shop.register') }}</a>
                        @elseif(Route::has('dev.login'))
                            {{-- Chỉ hiện khi nhánh chưa có trang Login của Điền (APP_ENV=local) --}}
                            <div class="dropdown">
                                <button class="btn btn-warning btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-lightning-charge-fill me-1"></i>Đăng nhập nhanh (DEV)</button>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li><a class="dropdown-item" href="{{ route('dev.login', 'admin') }}">Admin</a></li>
                                    <li><a class="dropdown-item" href="{{ route('dev.login', 'staff') }}">Nhân viên (Staff)</a></li>
                                    <li><a class="dropdown-item" href="{{ route('dev.login', 'customer') }}">Khách hàng</a></li>
                                </ul>
                            </div>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main class="container my-4 flex-grow-1">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <strong>Vui lòng kiểm tra lại dữ liệu:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="bg-dark text-light py-4 mt-auto">
        <div class="container">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-1"><i class="bi bi-cpu-fill text-info me-2"></i>TechStore B2C E-Commerce Platform</h6>
                    <p class="small text-secondary mb-0">Đề tài BTL Lập trình Web • Nhóm 5 Sinh viên: Điền, Tuyến, Phúc, Trung Anh, Nguyễn Minh Tự (20232059).</p>
                </div>
                <div class="col-md-6 text-md-end small text-secondary">
                    <a href="{{ url('/api/v1/products') }}" class="text-info text-decoration-none me-3" target="_blank"><i class="bi bi-braces me-1"></i>REST API v1 (/products)</a>
                    <a href="{{ url('/api/v1/stats') }}" class="text-info text-decoration-none me-3" target="_blank"><i class="bi bi-graph-up me-1"></i>API /stats</a>
                    <span>Laravel 9 MVC • MySQL InnoDB</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
