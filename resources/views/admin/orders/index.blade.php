@extends('layouts.admin')
@section('title', 'Quản lý Đơn hàng')
@section('page_title', 'Quản lý Đơn hàng')

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('admin.orders.index') }}" class="btn btn-sm {{ request('status') ? 'btn-outline-dark' : 'btn-dark' }}">Tất cả</a>
    @foreach($statuses as $key => $label)
        <a href="{{ route('admin.orders.index', ['status' => $key]) }}" class="btn btn-sm {{ request('status') === $key ? 'btn-primary' : 'btn-outline-primary' }}">
            {{ $label }} <span class="badge bg-light text-dark">{{ $counts[$key] ?? 0 }}</span>
        </a>
    @endforeach
</div>

<form method="GET" class="row g-2 mb-3">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
    <div class="col-md-5"><input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Mã đơn, tên hoặc SĐT khách hàng..."></div>
    <div class="col-md-3">
        <select name="payment_status" class="form-select">
            <option value="">Mọi thanh toán</option>
            <option value="unpaid" @selected(request('payment_status') === 'unpaid')>Chưa thanh toán</option>
            <option value="paid" @selected(request('payment_status') === 'paid')>Đã thanh toán</option>
            <option value="refunded" @selected(request('payment_status') === 'refunded')>Đã hoàn tiền</option>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-primary w-100"><i class="bi bi-search"></i> Lọc</button></div>
</form>

<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Mã đơn</th><th>Khách hàng</th><th class="text-center">SP</th><th class="text-end">Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th>Ngày đặt</th><th></th></tr></thead>
    <tbody>
    @forelse($orders as $o)
        <tr>
            <td class="fw-bold">{{ $o->order_code }}</td>
            <td>{{ $o->customer_name }}<div class="small text-muted">{{ $o->customer_phone }}</div></td>
            <td class="text-center">{{ $o->items_count }}</td>
            <td class="text-end">{{ number_format($o->total_amount, 0, ',', '.') }}₫</td>
            <td>{{ $o->payment_method === 'cod' ? 'COD' : 'QR' }} <span class="badge bg-{{ ['unpaid' => 'warning', 'paid' => 'success', 'refunded' => 'secondary'][$o->payment_status] ?? 'secondary' }}">{{ ['unpaid' => 'Chưa TT', 'paid' => 'Đã TT', 'refunded' => 'Hoàn tiền'][$o->payment_status] ?? $o->payment_status }}</span></td>
            <td><span class="badge bg-{{ $o->status_badge }}">{{ $o->status_label }}</span></td>
            <td>{{ $o->created_at->format('d/m/Y H:i') }}</td>
            <td><a href="{{ route('admin.orders.show', $o) }}" class="btn btn-sm btn-outline-primary">Xử lý</a></td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">Không có đơn hàng phù hợp.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>
@endsection
