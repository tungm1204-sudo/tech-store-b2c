@extends('layouts.app')
@section('title', 'Đơn hàng của tôi - TechStore')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h3 class="fw-bold mb-0"><i class="bi bi-bag-check text-primary me-2"></i>Đơn hàng của tôi</h3>
    <form method="GET" class="d-flex gap-2">
        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Tất cả trạng thái</option>
            @foreach($statuses as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

@forelse($orders as $order)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <a href="{{ route('orders.show', $order) }}" class="fw-bold text-decoration-none">{{ $order->order_code }}</a>
                <div class="small text-muted">{{ $order->created_at->format('d/m/Y H:i') }} • {{ $order->items_count }} sản phẩm •
                    {{ $order->payment_method === 'cod' ? 'COD' : 'Chuyển khoản QR' }}</div>
            </div>
            <div class="text-end">
                <span class="badge bg-{{ $order->status_badge }}">{{ $order->status_label }}</span>
                <div class="fw-bold text-danger">{{ number_format($order->total_amount, 0, ',', '.') }}₫</div>
            </div>
        </div>
    </div>
@empty
    <div class="text-center text-muted py-5"><i class="bi bi-inbox fs-1"></i><p class="mt-2">Chưa có đơn hàng nào.</p></div>
@endforelse

{{ $orders->links('pagination::bootstrap-5') }}
@endsection
