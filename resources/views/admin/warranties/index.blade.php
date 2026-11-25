@extends('layouts.admin')
@section('title', 'Đổi trả & Bảo hành RMA')
@section('page_title', 'Đổi trả & Bảo hành RMA')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.warranties.index') }}" class="btn btn-sm {{ request('status') ? 'btn-outline-dark' : 'btn-dark' }}">Tất cả</a>
    @foreach($statuses as $key => $label)
        <a href="{{ route('admin.warranties.index', ['status' => $key]) }}" class="btn btn-sm {{ request('status') === $key ? 'btn-primary' : 'btn-outline-primary' }}">
            {{ $label }} <span class="badge bg-light text-dark">{{ $counts[$key] ?? 0 }}</span>
        </a>
    @endforeach
</div>
<form method="GET" class="row g-2 mb-3">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <div class="col-md-6"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Mã RMA hoặc Serial/IMEI..."></div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Tìm</button></div>
</form>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Mã RMA</th><th>Khách hàng</th><th>Sản phẩm</th><th>Loại</th><th>Serial</th><th>Trạng thái</th><th>Ngày gửi</th><th></th></tr></thead>
    <tbody>
    @forelse($requests as $r)
        <tr>
            <td class="fw-bold">{{ $r->rma_code }}</td>
            <td>{{ $r->user->name ?? '—' }}</td>
            <td>{{ $r->orderItem->product_name ?? '—' }}<div class="small text-muted">{{ $r->order->order_code }}</div></td>
            <td>{{ $r->request_type === 'return' ? 'Đổi trả' : 'Bảo hành' }}</td>
            <td>{{ $r->serial_number }}</td>
            <td><span class="badge bg-{{ $r->status_badge }}">{{ $r->status_label }}</span></td>
            <td>{{ $r->created_at->format('d/m/Y') }}</td>
            <td><a href="{{ route('admin.warranties.show', $r) }}" class="btn btn-sm btn-outline-primary">Xử lý</a></td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">Chưa có yêu cầu nào.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $requests->links('pagination::bootstrap-5') }}</div>
@endsection
