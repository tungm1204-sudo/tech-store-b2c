@extends('layouts.app')
@section('title', 'Đổi trả & Bảo hành - TechStore')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold mb-0"><i class="bi bi-tools text-primary me-2"></i>Yêu cầu Đổi trả & Bảo hành</h3>
    <a href="{{ route('warranties.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tạo yêu cầu mới</a>
</div>
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead class="table-light"><tr><th>Mã RMA</th><th>Sản phẩm</th><th>Loại</th><th>Serial/IMEI</th><th>Trạng thái</th><th>Ngày gửi</th></tr></thead>
    <tbody>
    @forelse($requests as $r)
        <tr>
            <td><a href="{{ route('warranties.show', $r) }}" class="fw-bold text-decoration-none">{{ $r->rma_code }}</a></td>
            <td>{{ $r->orderItem->product_name ?? '—' }}<div class="small text-muted">Đơn {{ $r->order->order_code }}</div></td>
            <td>{{ $r->request_type === 'return' ? 'Đổi trả' : 'Bảo hành' }}</td>
            <td>{{ $r->serial_number }}</td>
            <td><span class="badge bg-{{ $r->status_badge }}">{{ $r->status_label }}</span></td>
            <td>{{ $r->created_at->format('d/m/Y') }}</td>
        </tr>
    @empty
        <tr><td colspan="6" class="text-center text-muted py-4">Bạn chưa có yêu cầu nào.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $requests->links('pagination::bootstrap-5') }}</div>
@endsection
