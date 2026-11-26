@extends('layouts.admin')
@section('title', 'Mã giảm giá')
@section('page_title', 'Mã giảm giá')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <form method="GET" class="d-flex gap-2">
        <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm mã...">
        <button class="btn btn-outline-primary">Tìm</button>
    </form>
    <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Thêm mã</a>
</div>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr><th>Mã</th><th>Giảm</th><th>Đơn tối thiểu</th><th>Đã dùng</th><th>Hết hạn</th><th>Trạng thái</th><th></th></tr>
            </thead>
            <tbody>
            @forelse($coupons as $c)
                <tr>
                    <td class="fw-bold">{{ $c->code }}</td>
                    <td>{{ $c->discount_type === 'percent' ? (int) $c->discount_value . '%' : number_format($c->discount_value, 0, ',', '.') . ' đ' }}</td>
                    <td>{{ number_format($c->min_order_amount, 0, ',', '.') }} đ</td>
                    <td>{{ $c->used_count }} / {{ $c->max_uses }}</td>
                    <td>{{ $c->expires_at ? $c->expires_at->format('d/m/Y H:i') : '—' }}</td>
                    <td>
                        @if(!$c->is_active) <span class="badge bg-secondary">Đã khóa</span>
                        @elseif($c->expires_at && $c->expires_at->isPast()) <span class="badge bg-warning text-dark">Hết hạn</span>
                        @elseif($c->used_count >= $c->max_uses) <span class="badge bg-warning text-dark">Hết lượt</span>
                        @else <span class="badge bg-success">Hoạt động</span> @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.coupons.edit', $c) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                        <form action="{{ route('admin.coupons.destroy', $c) }}" method="POST" class="d-inline" onsubmit="return confirm('Xóa mã {{ $c->code }}?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Xóa</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-secondary py-4">Chưa có mã giảm giá.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $coupons->links() }}</div>
@endsection
