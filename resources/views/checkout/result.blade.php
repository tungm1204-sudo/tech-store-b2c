@extends('layouts.app')

@section('title', 'Kết quả đơn hàng | TechStore B2C')

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . '₫';
    $shopUrl = ts_route(['products.index', 'dev.shop', 'home']);
@endphp
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <div class="text-center mb-4">
                @if($order->status === 'cancelled')
                    <div class="display-2 text-danger"><i class="bi bi-x-circle-fill"></i></div>
                    <h1 class="h3 fw-bold">Đơn hàng đã bị hủy</h1>
                @elseif($order->payment_status === 'refunded')
                    <div class="display-2 text-info"><i class="bi bi-arrow-counterclockwise"></i></div>
                    <h1 class="h3 fw-bold">Khoản thanh toán đã được hoàn</h1>
                @elseif($order->payment_status === 'paid' || $order->payment_method === 'cod')
                    <div class="display-2 text-success"><i class="bi bi-check-circle-fill"></i></div>
                    <h1 class="h3 fw-bold">Đã tiếp nhận đơn hàng</h1>
                @else
                    <div class="display-2 text-warning"><i class="bi bi-clock-history"></i></div>
                    <h1 class="h3 fw-bold">Đơn hàng đang chờ thanh toán</h1>
                @endif
                <p class="text-secondary mb-0">Cảm ơn bạn đã mua sắm tại TechStore.</p>
            </div>
            <div class="bg-light rounded-3 p-3 mb-4">
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Mã đơn hàng</span><strong>{{ $order->order_code }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Người nhận</span><strong>{{ $order->customer_name }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Trạng thái đơn hàng</span><strong>{{ $order->status_label }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Phương thức</span><strong>{{ $order->payment_method === 'cod' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản VietQR' }}</strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Thanh toán</span><strong>{{ ['paid' => 'Đã thanh toán', 'refunded' => 'Đã hoàn tiền', 'unpaid' => 'Chưa thanh toán'][$order->payment_status] ?? $order->payment_status }}</strong></div>
                <hr>
                <div class="d-flex justify-content-between"><span class="fw-semibold">Tổng tiền</span><strong class="fs-4 text-primary">{{ $money($order->total_amount) }}</strong></div>
            </div>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                @if($order->payment_method === 'qr_transfer' && $order->payment_status === 'unpaid' && $order->status !== 'cancelled')
                    <a href="{{ route('checkout.payment', $order) }}" class="btn btn-primary">Mở lại mã QR</a>
                @endif
                @if(Route::has('orders.index'))
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-primary">Lịch sử đơn hàng</a>
                @endif
                <a href="{{ $shopUrl }}" class="btn btn-outline-secondary">Tiếp tục mua sắm</a>
            </div>
        </div>
    </div>
</div>
@endsection
