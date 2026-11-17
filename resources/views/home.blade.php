@extends('layouts.app')

@section('title', 'TechStore B2C - Khung dự án (Ngày 1-2)')

@section('content')
<div class="ts-hero p-4 p-md-5 mb-4 shadow">
    <span class="badge bg-warning text-dark mb-2">KHUNG DỰ ÁN - NGÀY 1-2</span>
    <h1 class="fw-bold">TechStore B2C đã chạy thành công trên máy bạn 🎉</h1>
    <p class="mb-0 opacity-75">Nếu bạn thấy bảng số liệu bên dưới có số (không phải "Chưa có bảng"), nghĩa là CSDL 16 bảng và dữ liệu mẫu đã sẵn sàng. Hãy chuyển sang nhánh của mình và bắt đầu code!</p>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-database-check text-primary me-2"></i>Kiểm tra CSDL (16 bảng chuẩn hóa 3NF)</h5>
            <div class="row row-cols-2 row-cols-md-4 g-2">
                @foreach($tableStats as $table => $count)
                    <div class="col">
                        <div class="border rounded-3 p-2 h-100 {{ $count === null ? 'border-danger' : '' }}">
                            <div class="small text-muted text-truncate"><code>{{ $table }}</code></div>
                            <div class="fw-bold {{ $count === null ? 'text-danger' : '' }}">{{ $count === null ? 'Chưa có bảng' : $count . ' dòng' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="small text-muted mt-3 mb-0">Nếu có ô màu đỏ: chạy lại <code>php artisan migrate:fresh --seed</code>.</p>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-people-fill text-primary me-2"></i>Ai làm phần nào?</h5>
            <ul class="list-unstyled small mb-0">
                <li class="mb-2"><span class="badge bg-primary me-1">Điền</span> Đăng nhập, phân quyền, hồ sơ & sổ địa chỉ</li>
                <li class="mb-2"><span class="badge bg-success me-1">Tuyến</span> Danh mục, sản phẩm, tìm kiếm & bộ lọc, trang chủ bán hàng</li>
                <li class="mb-2"><span class="badge bg-danger me-1">Phúc</span> Giỏ hàng, mã giảm giá, thanh toán COD / QR</li>
                <li class="mb-2"><span class="badge bg-warning text-dark me-1">Trung Anh</span> Đơn hàng, đổi trả / bảo hành, nhập kho</li>
                <li><span class="badge bg-info text-dark me-1">Tự</span> Đánh giá sản phẩm, thống kê doanh thu, Git & triển khai</li>
            </ul>
        </div>

        @if(Route::has('dev.login'))
            <div class="card border-warning shadow-sm rounded-4 p-4">
                <h6 class="fw-bold"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Đăng nhập nhanh để test (chỉ khi APP_ENV=local)</h6>
                <p class="small text-muted">Dùng tạm trong lúc trang Đăng nhập của Điền chưa được ghép vào.</p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('dev.login', 'admin') }}" class="btn btn-sm btn-dark">Admin</a>
                    <a href="{{ route('dev.login', 'staff') }}" class="btn btn-sm btn-secondary">Nhân viên</a>
                    <a href="{{ route('dev.login', 'customer') }}" class="btn btn-sm btn-outline-primary">Khách hàng</a>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-4 mt-4">
    <h5 class="fw-bold mb-3"><i class="bi bi-laptop text-primary me-2"></i>Một vài sản phẩm mẫu trong CSDL</h5>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light"><tr><th>Tên sản phẩm</th><th class="text-end">Giá gốc</th><th class="text-end">Giá khuyến mãi</th><th class="text-end">Tồn kho</th></tr></thead>
            <tbody>
                @forelse($products as $p)
                    <tr>
                        <td>{{ $p->name }}</td>
                        <td class="text-end">{{ number_format($p->price, 0, ',', '.') }}₫</td>
                        <td class="text-end">{{ $p->sale_price ? number_format($p->sale_price, 0, ',', '.') . '₫' : '—' }}</td>
                        <td class="text-end">{{ $p->stock_quantity }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Chưa có dữ liệu. Hãy chạy <code>php artisan migrate:fresh --seed</code>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
