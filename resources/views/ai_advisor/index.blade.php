@extends('layouts.app')

@section('title', 'Trợ lý AI Tư vấn Thiết bị Công nghệ - TechStore B2C')

@section('content')
<div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
    <div class="row align-items-center g-3 mb-3">
        <div class="col-md-8">
            <h3 class="fw-bold text-primary mb-1"><i class="bi bi-stars text-warning me-2"></i>AI Tech Gadget Advisor – Chấm Điểm Phù Hợp Cấu Hình</h3>
            <p class="text-muted mb-0">Chọn danh mục, ngân sách và nhu cầu thực tế. Thuật toán trọng số AI sẽ chấm điểm (0–100%) và gợi ý thiết bị tối ưu nhất.</p>
        </div>
    </div>

    <form action="{{ ts_route('ai_advisor.index') }}" method="GET" class="row g-3 bg-light p-3 rounded-4">
        <div class="col-md-3">
            <label class="form-label small fw-bold">Danh mục mong muốn</label>
            <select name="category" class="form-select">
                <option value="all">-- Tất cả danh mục --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->slug }}" {{ request('category') === $cat->slug ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold">Ngân sách tối đa (VNĐ)</label>
            <input type="number" name="budget" value="{{ request('budget', 35000000) }}" step="500000" min="1000000" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold">Nhu cầu chính</label>
            <select name="usage_type" class="form-select">
                <option value="all" {{ request('usage_type') === 'all' ? 'selected' : '' }}>Đa nhiệm tổng hợp</option>
                <option value="gaming" {{ request('usage_type') === 'gaming' ? 'selected' : '' }}>Gaming & Esports</option>
                <option value="do-hoa" {{ request('usage_type') === 'do-hoa' ? 'selected' : '' }}>Đồ họa / Dựng phim / Lập trình</option>
                <option value="van-phong" {{ request('usage_type') === 'van-phong' ? 'selected' : '' }}>Văn phòng & Học tập</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold">Thương hiệu ưu tiên</label>
            <select name="brand" class="form-select">
                <option value="">-- Không giới hạn --</option>
                @foreach($brands as $br)
                    <option value="{{ $br->slug }}" {{ request('brand') === $br->slug ? 'selected' : '' }}>{{ $br->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 text-end">
            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-magic me-2"></i>Phân tích & Gợi ý Ngay</button>
        </div>
    </form>
</div>

@if($recommendations !== null)
    <h5 class="fw-bold mb-3">Kết quả AI Gợi ý ({{ $recommendations->count() }} thiết bị phù hợp nhất):</h5>
    <div class="row g-3">
        @forelse($recommendations as $item)
            <div class="col-md-4">
                <div class="product-card h-100 d-flex flex-column">
                    <div class="position-relative">
                        <img src="{{ $item->thumbnail }}" class="w-100" style="height: 165px; object-fit: cover;">
                        <span class="position-absolute top-0 start-0 m-2 badge bg-warning text-dark fs-6 fw-bold">Độ khớp {{ $item->ai_match_score }}%</span>
                    </div>
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <a href="{{ ts_route('products.show', $item->slug) }}" class="fw-bold text-dark text-decoration-none">{{ $item->name }}</a>
                        <div class="text-danger fw-bold fs-5 my-1">{{ number_format($item->effective_price) }}₫</div>
                        <ul class="small text-success ps-3 mb-3">
                            @foreach($item->ai_reasons as $rs)
                                <li>{{ $rs }}</li>
                            @endforeach
                        </ul>
                        <div class="mt-auto d-flex gap-1">
                            <a href="{{ ts_route('products.show', $item->slug) }}" class="btn btn-sm btn-primary flex-grow-1">Xem chi tiết</a>
                            <a href="{{ ts_route('compare.add', $item) }}" class="btn btn-sm btn-outline-secondary">So sánh</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-warning">Chưa tìm thấy sản phẩm phù hợp tầm ngân sách này. Hãy thử tăng mức ngân sách lên một chút!</div></div>
        @endforelse
    </div>
@endif
@endsection
