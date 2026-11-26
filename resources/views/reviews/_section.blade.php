{{--
    KHỐI ĐÁNH GIÁ & BÌNH LUẬN SẢN PHẨM (Phụ trách: Nguyễn Minh Tự - 20232059)
    Cách nhúng vào trang chi tiết sản phẩm (Tuyến chỉ cần thêm 1 dòng):
        @includeIf('reviews._section', ['product' => $product])
--}}
@php
    $rs = app(\App\Services\ReviewService::class)->sectionData($product, request());
    $baseUrl = url()->current();
    $mine = $rs['myReview'];
@endphp

<div id="reviews-section" class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h4 class="fw-bold mb-0"><i class="bi bi-star-fill text-warning me-2"></i>Đánh giá & Nhận xét</h4>
        <span class="badge bg-primary-subtle text-primary">Huy hiệu "Đã mua hàng" tự động xác minh từ đơn đã giao</span>
    </div>

    {{-- Tổng quan điểm --}}
    <div class="row g-4 align-items-center bg-light rounded-4 p-3 mb-4 mx-0">
        <div class="col-md-4 text-center border-end">
            <div class="display-4 fw-bold text-warning">{{ $rs['avgRating'] }}<small class="fs-5 text-muted">/5</small></div>
            <div class="text-warning fs-5 mb-1">
                @for($i = 1; $i <= 5; $i++)
                    <i class="bi {{ $i <= round($rs['avgRating']) ? 'bi-star-fill' : 'bi-star' }}"></i>
                @endfor
            </div>
            <div class="small text-muted">{{ $rs['totalReviews'] }} lượt đánh giá</div>
        </div>
        <div class="col-md-8">
            @foreach($rs['ratingBreakdown'] as $star => $info)
                <div class="d-flex align-items-center gap-2 mb-1">
                    <span class="small fw-semibold" style="width: 48px;">{{ $star }} <i class="bi bi-star-fill text-warning"></i></span>
                    <div class="progress flex-grow-1" style="height: 10px;">
                        <div class="progress-bar bg-warning" style="width: {{ $info['percent'] }}%"></div>
                    </div>
                    <span class="small text-muted" style="width: 70px;">{{ $info['count'] }} ({{ $info['percent'] }}%)</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Bộ lọc --}}
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ $baseUrl }}#reviews-section" class="btn btn-sm {{ !request('star') && !request('verified_only') ? 'btn-primary' : 'btn-outline-secondary' }}">Tất cả ({{ $rs['totalReviews'] }})</a>
        @for($s = 5; $s >= 1; $s--)
            <a href="{{ $baseUrl }}?star={{ $s }}#reviews-section" class="btn btn-sm {{ (int) request('star') === $s ? 'btn-warning' : 'btn-outline-secondary' }}">{{ $s }} Sao ({{ $rs['ratingBreakdown'][$s]['count'] }})</a>
        @endfor
        <a href="{{ $baseUrl }}?verified_only=1#reviews-section" class="btn btn-sm {{ request('verified_only') ? 'btn-success' : 'btn-outline-success' }}">
            <i class="bi bi-patch-check-fill me-1"></i>Đã mua hàng
        </a>
    </div>

    {{-- Form viết / sửa đánh giá --}}
    @auth
        <div class="card border border-primary-subtle rounded-4 p-3 mb-4 bg-white">
            <h6 class="fw-bold text-primary mb-2">
                <i class="bi bi-pencil-square me-1"></i>{{ $mine ? 'Sửa đánh giá của bạn' : 'Viết đánh giá của bạn' }}
                @if($rs['hasPurchased'])
                    <span class="badge bg-success ms-2">Bạn đã mua sản phẩm này - sẽ gắn "Đã mua hàng"</span>
                @endif
            </h6>
            <form action="{{ route('reviews.store', $product) }}" method="POST">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Chấm điểm (1-5 sao) *</label>
                        <select name="rating" class="form-select form-select-sm" required>
                            @foreach([5 => 'Xuất sắc', 4 => 'Rất tốt', 3 => 'Khá ổn', 2 => 'Trung bình', 1 => 'Chưa hài lòng'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('rating', $mine->rating ?? 5) == $val)>{{ $val }} Sao - {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">Tiêu đề</label>
                        <input type="text" name="title" maxlength="180" value="{{ old('title', $mine->title ?? '') }}" class="form-control form-control-sm" placeholder="VD: Hiệu năng rất tốt trong tầm giá">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-success">Ưu điểm</label>
                        <input type="text" name="pros" maxlength="255" value="{{ old('pros', $mine->pros ?? '') }}" class="form-control form-control-sm" placeholder="VD: Pin trâu, màn đẹp">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-danger">Nhược điểm</label>
                        <input type="text" name="cons" maxlength="255" value="{{ old('cons', $mine->cons ?? '') }}" class="form-control form-control-sm" placeholder="VD: Hơi nặng">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Nội dung nhận xét *</label>
                        <textarea name="comment" rows="3" minlength="5" maxlength="2000" class="form-control form-control-sm" required placeholder="Chia sẻ trải nghiệm thực tế của bạn...">{{ old('comment', $mine->comment ?? '') }}</textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary btn-sm px-4"><i class="bi bi-send-fill me-1"></i>{{ $mine ? 'Cập nhật đánh giá' : 'Gửi đánh giá' }}</button>
                    </div>
                </div>
            </form>
        </div>
    @else
        <div class="alert alert-light border mb-4">
            Vui lòng <a href="{{ ts_route(['login', 'home']) }}" class="fw-bold">đăng nhập</a> để viết đánh giá.
        </div>
    @endauth

    {{-- Danh sách đánh giá --}}
    @forelse($rs['reviews'] as $rv)
        <div class="border-bottom pb-3 mb-3">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                    <strong class="me-2">{{ $rv->user?->name ?? 'Khách hàng' }}</strong>
                    @if($rv->is_verified_purchase)
                        <span class="badge bg-success-subtle text-success"><i class="bi bi-patch-check-fill me-1"></i>Đã mua hàng tại TechStore</span>
                    @endif
                    <div class="text-warning small mt-1">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="bi {{ $i <= $rv->rating ? 'bi-star-fill' : 'bi-star' }}"></i>
                        @endfor
                        <span class="text-muted ms-2">{{ $rv->updated_at?->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
                @if(auth()->check() && (auth()->id() === $rv->user_id || auth()->user()->isStaffOrAdmin()))
                    <form action="{{ route('reviews.destroy', $rv) }}" method="POST" onsubmit="return confirm('Xóa đánh giá này?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm" title="Xóa"><i class="bi bi-trash"></i></button>
                    </form>
                @endif
            </div>

            @if($rv->title)<div class="fw-bold mt-2">{{ $rv->title }}</div>@endif
            @if($rv->pros)<div class="small text-success mt-1"><i class="bi bi-plus-circle-fill me-1"></i><strong>Ưu điểm:</strong> {{ $rv->pros }}</div>@endif
            @if($rv->cons)<div class="small text-danger"><i class="bi bi-dash-circle-fill me-1"></i><strong>Nhược điểm:</strong> {{ $rv->cons }}</div>@endif
            <p class="mt-2 mb-2">{{ $rv->comment }}</p>

            @if($rv->admin_reply)
                <div class="bg-primary-subtle border-start border-4 border-primary rounded-3 p-3 ms-md-4 mt-2">
                    <div class="small fw-bold text-primary mb-1"><i class="bi bi-shield-check me-1"></i>Phản hồi từ TechStore ({{ $rv->replied_at?->format('d/m/Y') }})</div>
                    <div class="small mb-0">{{ $rv->admin_reply }}</div>
                </div>
            @endif
        </div>
    @empty
        <p class="text-muted">Chưa có đánh giá nào khớp bộ lọc.</p>
    @endforelse

    <div class="mt-3">{{ $rs['reviews']->fragment('reviews-section')->links() }}</div>
</div>
