@extends('layouts.app')

@section('title', $product->name . ' - TechStore B2C')

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-decoration-none">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="{{ route('products.index') }}" class="text-decoration-none">Sản phẩm</a></li>
        @if($product->category)
            <li class="breadcrumb-item"><a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="text-decoration-none">{{ $product->category->name }}</a></li>
        @endif
        <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($product->name, 48) }}</li>
    </ol>
</nav>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="row g-4">
        {{-- Cột Ảnh sản phẩm --}}
        <div class="col-lg-5">
            @php
                $mainImg = $product->thumbnail ? asset(ltrim($product->thumbnail, '/')) : null;
            @endphp
            <div class="bg-light rounded-4 d-flex align-items-center justify-content-center p-4 mb-3" style="min-height: 320px;">
                @if ($mainImg)
                    <img id="mainProductImg" src="{{ $mainImg }}" alt="{{ $product->name }}" class="img-fluid" style="max-height: 290px; object-fit: contain;">
                @else
                    <i class="bi bi-laptop display-1 text-secondary"></i>
                @endif
            </div>

            @if($product->images->count() > 1)
                <div class="d-flex gap-2 flex-wrap">
                    @foreach($product->images as $img)
                        @php $imgUrl = asset(ltrim($img->image_path, '/')); @endphp
                        <button type="button" class="btn btn-light border p-1 rounded-3"
                                onclick="document.getElementById('mainProductImg').src='{{ $imgUrl }}'">
                            <img src="{{ $imgUrl }}" alt="" width="56" height="56" style="object-fit: contain;">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Cột Thông tin & Chọn mua --}}
        <div class="col-lg-7">
            <div class="d-flex align-items-center gap-2 mb-2">
                @if($product->brand)<span class="badge bg-primary-subtle text-primary">{{ $product->brand->name }}</span>@endif
                @if($product->category)<span class="badge bg-light text-dark border">{{ $product->category->name }}</span>@endif
                <small class="text-muted ms-auto">SKU: <code>{{ $product->sku }}</code></small>
            </div>

            <h1 class="h3 fw-bold mb-2">{{ $product->name }}</h1>

            <div class="d-flex align-items-center gap-3 small mb-3">
                <a href="#reviews-section" class="text-decoration-none text-dark">
                    <i class="bi bi-star-fill text-warning"></i>
                    <strong>{{ $product->approved_reviews_count > 0 ? number_format($product->approved_reviews_avg_rating, 1) : '0.0' }}</strong>
                    <span class="text-muted">({{ $product->approved_reviews_count }} đánh giá)</span>
                </a>
                <span class="text-muted">•</span>
                <span class="text-muted">Đã bán: <strong>{{ $product->sold_count }}</strong></span>
                <span class="text-muted">•</span>
                <span class="text-success"><i class="bi bi-shield-check me-1"></i>Bảo hành {{ $product->warranty_months ?? 12 }} tháng</span>
            </div>

            <div class="bg-light rounded-3 p-3 mb-3">
                <div class="d-flex align-items-baseline gap-2 flex-wrap">
                    <span id="livePrice" class="display-6 fw-bold text-danger">{{ number_format($product->effective_price, 0, ',', '.') }}₫</span>
                    @if ($product->discount_percent > 0)
                        <span class="text-muted text-decoration-line-through fs-5">{{ number_format($product->price, 0, ',', '.') }}₫</span>
                        <span class="badge bg-danger">-{{ $product->discount_percent }}%</span>
                    @endif
                </div>
                <div class="small mt-1" id="liveStock">
                    @if($product->stock_quantity > 0)
                        <span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Còn hàng ({{ $product->stock_quantity }} sản phẩm)</span>
                    @else
                        <span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i>Tạm hết hàng</span>
                    @endif
                </div>
            </div>

            @if($product->short_description)
                <p class="text-secondary mb-3">{{ $product->short_description }}</p>
            @endif

            {{-- Form Thêm vào giỏ hàng (kết nối với module Giỏ hàng của Phúc) --}}
            @if(Route::has('cart.add'))
                <form action="{{ route('cart.add') }}" method="POST" class="border rounded-4 p-3 bg-white mb-3">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                    @if($product->variants->isNotEmpty())
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Chọn phiên bản / cấu hình *</label>
                            @php $firstAvailableVariantId = $product->variants->firstWhere('stock_quantity', '>', 0)?->id; @endphp
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($product->variants as $variant)
                                    @php
                                        $vPrice = max(0, (float) $product->effective_price + (float) $variant->price_adjustment);
                                        $disabled = $variant->stock_quantity <= 0;
                                    @endphp
                                    <input type="radio" class="btn-check variant-radio" name="variant_id"
                                           id="variant_{{ $variant->id }}" value="{{ $variant->id }}"
                                           data-price="{{ $vPrice }}"
                                           data-stock="{{ $variant->stock_quantity }}"
                                           @checked(!$disabled && $variant->id === $firstAvailableVariantId)
                                           @disabled($disabled) required>
                                    <label class="btn btn-outline-primary btn-sm rounded-3" for="variant_{{ $variant->id }}">
                                        {{ $variant->variant_name }}
                                        @if((float) $variant->price_adjustment > 0)
                                            <small>(+{{ number_format($variant->price_adjustment, 0, ',', '.') }}₫)</small>
                                        @endif
                                        @if($disabled) <small class="text-danger">(Hết)</small> @endif
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="row g-2 align-items-center">
                        <div class="col-auto">
                            <label class="form-label small fw-semibold mb-0 me-2">Số lượng:</label>
                        </div>
                        <div class="col-auto">
                            <input type="number" name="quantity" id="qtyInput" value="1" min="1" max="{{ max(1, $product->stock_quantity) }}"
                                   class="form-control form-control-sm text-center" style="width: 80px;" @disabled($product->stock_quantity <= 0)>
                        </div>
                        <div class="col">
                            <button type="submit" class="btn btn-primary w-100 fw-semibold" @disabled($product->stock_quantity <= 0)>
                                <i class="bi bi-cart-plus me-1"></i>Thêm vào giỏ hàng
                            </button>
                        </div>
                    </div>
                </form>
            @endif

            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Quay lại danh sách sản phẩm
            </a>
        </div>
    </div>
</div>

{{-- Mô tả & Thông số kỹ thuật --}}
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-file-text text-primary me-2"></i>Mô tả sản phẩm</h5>
            <div class="text-secondary" style="line-height: 1.7;">
                {!! nl2br(e($product->description ?: $product->short_description ?: 'Đang cập nhật mô tả chi tiết.')) !!}
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-cpu text-primary me-2"></i>Thông số kỹ thuật</h5>
            <table class="table table-sm table-striped mb-0 small">
                <tbody>
                    <tr><th class="text-muted w-35">CPU / Chip</th><td>{{ $product->cpu ?: '—' }}</td></tr>
                    <tr><th class="text-muted">RAM</th><td>{{ $product->ram ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Bộ nhớ (ROM)</th><td>{{ $product->rom ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Đồ họa (GPU)</th><td>{{ $product->gpu ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Màn hình</th><td>{{ $product->screen ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Pin / Sạc</th><td>{{ $product->battery ?: '—' }}</td></tr>
                    <tr><th class="text-muted">Bảo hành</th><td>{{ $product->warranty_months ?? 12 }} tháng chính hãng</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Khối Đánh giá & Bình luận Verified Purchase (Module của Nguyễn Minh Tự) --}}
@includeIf('reviews._section', ['product' => $product])
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const radios = document.querySelectorAll('.variant-radio');
    const livePrice = document.getElementById('livePrice');
    const liveStock = document.getElementById('liveStock');
    const qtyInput = document.getElementById('qtyInput');
    const fmt = new Intl.NumberFormat('vi-VN');

    function syncVariant(radio) {
        if (!radio || !livePrice) return;
        const price = Number(radio.dataset.price || 0);
        const stock = Number(radio.dataset.stock || 0);
        livePrice.textContent = fmt.format(price) + '₫';
        if (liveStock) {
            liveStock.innerHTML = stock > 0
                ? '<span class="text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i>Phiên bản còn ' + stock + ' sản phẩm</span>'
                : '<span class="text-danger fw-semibold"><i class="bi bi-x-circle-fill me-1"></i>Phiên bản tạm hết hàng</span>';
        }
        if (qtyInput && stock > 0) {
            qtyInput.max = stock;
            if (Number(qtyInput.value) > stock) qtyInput.value = stock;
        }
    }

    radios.forEach(r => {
        r.addEventListener('change', () => syncVariant(r));
        if (r.checked) syncVariant(r);
    });
});
</script>
@endpush
