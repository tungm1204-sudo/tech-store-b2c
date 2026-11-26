@extends('layouts.app')
@section('title', 'Đơn hàng ' . $order->order_code)

@section('content')
@php
    $labels = \App\Models\Order::STATUS_LABELS;
    $currentIdx = array_search($order->status, $flow, true);
@endphp
<a href="{{ route('orders.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Đơn hàng của tôi</a>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 my-2">
    <h3 class="fw-bold mb-0">Đơn {{ $order->order_code }}</h3>
    <span class="badge fs-6 bg-{{ $order->status_badge }}">{{ $order->status_label }}</span>
</div>

@if($order->status === 'cancelled')
    <div class="alert alert-danger"><i class="bi bi-x-octagon me-1"></i>Đơn đã hủy: {{ $order->cancelled_reason }}</div>
@else
    <div class="card border-0 shadow-sm mb-3"><div class="card-body">
        <div class="d-flex justify-content-between text-center">
            @foreach($flow as $i => $step)
                <div class="flex-fill">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center {{ $i <= $currentIdx ? 'bg-success text-white' : 'bg-light text-muted border' }}" style="width:36px;height:36px">
                        @if($i < $currentIdx)<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif
                    </div>
                    <div class="small mt-1 {{ $i === $currentIdx ? 'fw-bold' : 'text-muted' }}">{{ $labels[$step] }}</div>
                </div>
            @endforeach
        </div>
    </div></div>
@endif

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm"><div class="card-header bg-white fw-bold">Sản phẩm</div>
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Sản phẩm</th><th class="text-end">Đơn giá</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th><th></th></tr></thead>
                <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}@if($item->variant_info)<div class="small text-muted">{{ $item->variant_info }}</div>@endif</td>
                        <td class="text-end">{{ number_format($item->unit_price, 0, ',', '.') }}₫</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->line_total, 0, ',', '.') }}₫</td>
                        <td class="text-end">
                            @if($order->status === 'delivered')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('warranties.create', ['order_item' => $item->id]) }}"><i class="bi bi-tools"></i> Bảo hành / Đổi trả</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3"><div class="card-body small">
            <div class="d-flex justify-content-between"><span>Tạm tính</span><span>{{ number_format($order->subtotal, 0, ',', '.') }}₫</span></div>
            <div class="d-flex justify-content-between"><span>Giảm giá</span><span>-{{ number_format($order->discount_amount, 0, ',', '.') }}₫</span></div>
            <div class="d-flex justify-content-between"><span>Phí vận chuyển</span><span>{{ number_format($order->shipping_fee, 0, ',', '.') }}₫</span></div>
            <hr>
            <div class="d-flex justify-content-between fw-bold fs-6"><span>Tổng cộng</span><span class="text-danger">{{ number_format($order->total_amount, 0, ',', '.') }}₫</span></div>
            <div class="mt-2">Thanh toán: {{ $order->payment_method === 'cod' ? 'COD' : 'Chuyển khoản QR' }} —
                <span class="badge bg-{{ ['unpaid' => 'warning', 'paid' => 'success', 'refunded' => 'secondary'][$order->payment_status] ?? 'secondary' }}">{{ ['unpaid' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán', 'refunded' => 'Đã hoàn tiền'][$order->payment_status] ?? $order->payment_status }}</span></div>
        </div></div>
        <div class="card border-0 shadow-sm mb-3"><div class="card-body small">
            <div class="fw-bold mb-1">Giao tới</div>
            {{ $order->customer_name }} • {{ $order->customer_phone }}<br>{{ $order->shipping_address }}
            @if($order->note)<div class="text-muted mt-1">Ghi chú: {{ $order->note }}</div>@endif
        </div></div>

        @if($order->canCustomerCancel())
            <form method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Bạn chắc chắn muốn hủy đơn này?')">
                @csrf
                <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Lý do hủy (không bắt buộc)" maxlength="255">
                <button class="btn btn-outline-danger w-100"><i class="bi bi-x-circle me-1"></i>Hủy đơn hàng</button>
            </form>
        @endif
        @if($order->status === 'shipping')
            <form method="POST" action="{{ route('orders.received', $order) }}">
                @csrf
                <button class="btn btn-success w-100"><i class="bi bi-box-seam me-1"></i>Tôi đã nhận được hàng</button>
            </form>
        @endif
    </div>
</div>
@endsection
