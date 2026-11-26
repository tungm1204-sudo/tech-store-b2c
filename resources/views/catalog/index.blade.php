@extends('layouts.app')

@section('title', 'Danh mục Thiết bị Công nghệ - TechStore B2C')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <span class="text-primary fw-semibold small text-uppercase">Danh mục sản phẩm</span>
        <h1 class="h3 fw-bold mb-0">Thiết bị Công nghệ Chính hãng</h1>
        <small class="text-muted">Tìm thấy <strong>{{ $products->total() }}</strong> sản phẩm phù hợp</small>
    </div>

    <form method="GET" action="{{ route('products.index') }}" class="d-flex align-items-center gap-2">
        @foreach(request()->except(['sort', 'page']) as $key => $val)
            @if(is_array($val))
                @foreach($val as $v)
                    <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                @endforeach
            @else
                <input type="hidden" name="{{ $key }}" value="{{ $val }}">
            @endif
        @endforeach
        <label class="small text-muted text-nowrap">Sắp xếp:</label>
        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="newest" @selected($sort === 'newest')>Nổi bật & Mới nhất</option>
            <option value="bestseller" @selected($sort === 'bestseller')>Bán chạy nhất</option>
            <option value="price_asc" @selected($sort === 'price_asc')>Giá thấp đến cao</option>
            <option value="price_desc" @selected($sort === 'price_desc')>Giá cao đến thấp</option>
        </select>
    </form>
</div>

<div class="row g-4">
    {{-- Cột Bộ lọc --}}
    <div class="col-lg-3">
        <form method="GET" action="{{ route('products.index') }}" class="card border-0 shadow-sm rounded-4 p-3 position-sticky" style="top: 84px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-funnel text-primary me-1"></i>Bộ lọc nâng cao</h6>
                <a href="{{ route('products.index') }}" class="small text-decoration-none">Đặt lại</a>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Từ khóa</label>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tên máy, SKU, CPU...">
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Danh mục</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">Tất cả danh mục</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($selectedCategoryId == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Khoảng giá (₫)</label>
                <div class="row g-2">
                    <div class="col-6">
                        <input type="number" name="min" value="{{ request('min') }}" class="form-control form-control-sm" placeholder="Từ">
                    </div>
                    <div class="col-6">
                        <input type="number" name="max" value="{{ request('max') }}" class="form-control form-control-sm" placeholder="Đến">
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Thương hiệu</label>
                <div class="d-flex flex-column gap-1" style="max-height: 150px; overflow-y: auto;">
                    @foreach ($brands as $brand)
                        <div class="form-check small">
                            <input class="form-check-input" type="checkbox" name="brand[]" value="{{ $brand->id }}"
                                   id="brand_{{ $brand->id }}"
                                   @checked(in_array($brand->id, (array) request('brand', [])))>
                            <label class="form-check-label" for="brand_{{ $brand->id }}">{{ $brand->name }}</label>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Nhu cầu sử dụng</label>
                <select name="usage" class="form-select form-select-sm">
                    <option value="">Mọi nhu cầu</option>
                    @foreach ($usageOptions as $value => $label)
                        <option value="{{ $value }}" @selected(request('usage') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label small fw-semibold">RAM</label>
                    <select name="ram" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        @foreach ($ramOptions as $ram)
                            <option value="{{ $ram }}" @selected(request('ram') == $ram)>{{ $ram }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6">
                    <label class="form-label small fw-semibold">Bộ nhớ</label>
                    <select name="storage" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        @foreach ($storageOptions as $storage)
                            <option value="{{ $storage }}" @selected(request('storage') == $storage)>{{ $storage }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label small fw-semibold">Màu sắc</label>
                <select name="color" class="form-select form-select-sm">
                    <option value="">Mọi màu</option>
                    @foreach ($colorOptions as $color)
                        <option value="{{ $color }}" @selected(request('color') == $color)>{{ $color }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-check mb-3 small">
                <input class="form-check-input" type="checkbox" name="ssd" value="1" id="filter_ssd" @checked(request('ssd'))>
                <label class="form-check-label" for="filter_ssd">Chỉ ổ cứng SSD / NVMe</label>
            </div>

            @if(request('sort'))
                <input type="hidden" name="sort" value="{{ request('sort') }}">
            @endif

            <button type="submit" class="btn btn-primary btn-sm w-100 fw-semibold">
                <i class="bi bi-search me-1"></i>Áp dụng bộ lọc
            </button>
        </form>
    </div>

    {{-- Cột Danh sách sản phẩm --}}
    <div class="col-lg-9">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3">
            @forelse ($products as $product)
                <div class="col">
                    <div class="product-card h-100 d-flex flex-column p-3">
                        <a href="{{ route('products.show', $product->slug) }}" class="text-decoration-none text-dark">
                            <div class="bg-light rounded-3 d-flex align-items-center justify-content-center p-3 mb-3 position-relative" style="height: 185px;">
                                @if ($product->discount_percent > 0)
                                    <span class="badge bg-danger position-absolute top-0 start-0 m-2">-{{ $product->discount_percent }}%</span>
                                @endif
                                @if ($product->thumbnail)
                                    <img src="{{ asset(ltrim($product->thumbnail, '/')) }}" alt="{{ $product->name }}" class="img-fluid" style="max-height: 155px; object-fit: contain;">
                                @else
                                    <i class="bi bi-laptop fs-1 text-secondary"></i>
                                @endif
                            </div>
                            <div class="small text-muted mb-1">
                                {{ $product->brand->name ?? '' }} • {{ $product->category->name ?? '' }}
                            </div>
                            <h6 class="fw-bold mb-2" style="min-height: 2.5rem;">{{ Str::limit($product->name, 58) }}</h6>
                        </a>

                        <div class="mb-2">
                            @if($product->cpu)<span class="spec-pill">{{ Str::limit($product->cpu, 22) }}</span>@endif
                            @if($product->ram)<span class="spec-pill">{{ $product->ram }}</span>@endif
                            @if($product->rom)<span class="spec-pill">{{ $product->rom }}</span>@endif
                        </div>

                        <div class="mt-auto pt-2 border-top">
                            <div class="d-flex align-items-baseline gap-2 mb-1">
                                <span class="fs-6 fw-bold text-danger">{{ number_format($product->effective_price, 0, ',', '.') }}₫</span>
                                @if ($product->discount_percent > 0)
                                    <small class="text-muted text-decoration-line-through">{{ number_format($product->price, 0, ',', '.') }}₫</small>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between align-items-center small text-muted">
                                <span>
                                    <i class="bi bi-star-fill text-warning"></i>
                                    {{ $product->approved_reviews_count > 0 ? number_format($product->approved_reviews_avg_rating, 1) : 'Mới' }}
                                    ({{ $product->approved_reviews_count }})
                                </span>
                                <span class="{{ $product->stock_quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $product->stock_quantity > 0 ? 'Còn ' . $product->stock_quantity : 'Hết hàng' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                        <i class="bi bi-search fs-1 text-muted mb-2"></i>
                        <h5 class="fw-bold">Không tìm thấy sản phẩm nào</h5>
                        <p class="text-muted small mb-3">Thử thay đổi bộ lọc hoặc xóa từ khóa tìm kiếm.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-primary btn-sm mx-auto">Xem tất cả sản phẩm</a>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection
