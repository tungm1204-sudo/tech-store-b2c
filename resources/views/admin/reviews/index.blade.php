@extends('layouts.admin')

@section('title', 'Quản lý Đánh giá & Bình luận - Admin TechStore')
@section('page_title', 'Quản Lý Đánh Giá, Kiểm Duyệt & Phản Hồi (Nguyễn Minh Tự)')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card kpi-card p-3 bg-white"><small class="text-muted">Tổng đánh giá</small><div class="fs-4 fw-bold text-primary">{{ $summary['total'] }}</div></div></div>
    <div class="col-md-3"><div class="card kpi-card p-3 bg-white"><small class="text-muted">Điểm trung bình toàn sàn</small><div class="fs-4 fw-bold text-warning">{{ $summary['avg_rating'] }} / 5★</div></div></div>
    <div class="col-md-3"><div class="card kpi-card p-3 bg-white"><small class="text-muted">Khách đã mua (Verified)</small><div class="fs-4 fw-bold text-success">{{ $summary['verified_count'] }}</div></div></div>
    <div class="col-md-3"><div class="card kpi-card p-3 bg-white"><small class="text-muted">Chưa phản hồi / Đang ẩn</small><div class="fs-4 fw-bold text-danger">{{ $summary['pending_reply'] }} / {{ $summary['hidden_count'] }}</div></div></div>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
    <form action="{{ ts_route('admin.reviews.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3"><input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Tìm tên SP, khách hoặc nội dung..."></div>
        <div class="col-md-2">
            <select name="rating" class="form-select form-select-sm">
                <option value="">-- Số sao --</option>
                @for($s=5; $s>=1; $s--)
                    <option value="{{ $s }}" {{ request('rating') == $s ? 'selected' : '' }}>{{ $s }} Sao</option>
                @endfor
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">-- Hiển thị --</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Đang hiển thị</option>
                <option value="hidden" {{ request('status') === 'hidden' ? 'selected' : '' }}>Đã ẩn</option>
            </select>
        </div>
        <div class="col-md-2 form-check ms-2">
            <input type="checkbox" name="unreplied_only" value="1" class="form-check-input" id="unrep" {{ request('unreplied_only') ? 'checked' : '' }}>
            <label class="form-check-label small" for="unrep">Chưa phản hồi</label>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">Lọc đánh giá</button>
        </div>
    </form>
</div>

<div class="card border-0 shadow-sm rounded-4 p-3">
    @forelse($reviews as $rv)
        <div class="border rounded-3 p-3 mb-3 {{ !$rv->is_approved ? 'bg-light opacity-75' : 'bg-white' }}">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                <div>
                    <span class="badge bg-warning text-dark me-1">{{ $rv->rating }} ★</span>
                    <strong>{{ $rv->user?->name }}</strong>
                    @if($rv->is_verified_purchase)<span class="badge bg-success ms-1">Verified Purchase</span>@endif
                    <span class="badge {{ $rv->is_approved ? 'bg-info text-dark' : 'bg-secondary' }} ms-1">{{ $rv->is_approved ? 'Hiển thị' : 'Đang ẩn' }}</span>
                    <div class="small text-primary fw-semibold mt-1">Sản phẩm: {{ $rv->product?->name }}</div>
                </div>
                <div class="d-flex gap-1">
                    <form action="{{ ts_route('admin.reviews.toggle', $rv) }}" method="POST">
                        @csrf @method('PATCH')
                        <button class="btn btn-sm {{ $rv->is_approved ? 'btn-outline-secondary' : 'btn-outline-success' }}">
                            {{ $rv->is_approved ? 'Ẩn bình luận' : 'Duyệt hiển thị' }}
                        </button>
                    </form>
                    <form action="{{ ts_route('admin.reviews.destroy', $rv) }}" method="POST" onsubmit="return confirm('Xóa vĩnh viễn đánh giá này?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>

            @if($rv->title)<div class="fw-bold small">{{ $rv->title }}</div>@endif
            <p class="small mb-2">{{ $rv->comment }}</p>

            <form action="{{ ts_route('admin.reviews.reply', $rv) }}" method="POST" class="d-flex gap-2 mt-2">
                @csrf
                <input type="text" name="admin_reply" value="{{ $rv->admin_reply }}" class="form-control form-control-sm" placeholder="Nhập phản hồi chính thức từ TechStore Admin/CSKH..." required>
                <button class="btn btn-sm btn-primary text-nowrap"><i class="bi bi-reply-fill me-1"></i>{{ $rv->admin_reply ? 'Cập nhật Reply' : 'Gửi Reply' }}</button>
            </form>
        </div>
    @empty
        <p class="text-muted text-center py-4">Không có đánh giá nào phù hợp.</p>
    @endforelse
    {{ $reviews->links() }}
</div>
@endsection
