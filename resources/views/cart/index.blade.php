@extends('layouts.app')

@section('title', 'Giỏ hàng | TechStore B2C')

@section('content')
@php
    $items = $cart ? $cart->items : collect();
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . '₫';
@endphp

<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <span class="text-primary fw-semibold small text-uppercase">Mua sắm / Giỏ hàng</span>
        <h1 class="fw-bold mb-1">Giỏ hàng của bạn</h1>
        <p class="text-secondary mb-0">Kiểm tra sản phẩm và mã ưu đãi trước khi thanh toán.</p>
    </div>
    <a href="{{ ts_route('products.index') }}" class="btn btn-outline-primary rounded-pill">
        <i class="bi bi-arrow-left me-1"></i> Tiếp tục mua sắm
    </a>
</div>

@if($items->isEmpty())
    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
        <div class="display-3 text-primary mb-3"><i class="bi bi-cart-x"></i></div>
        <h3 class="fw-bold">Giỏ hàng đang trống</h3>
        <p class="text-secondary">Hãy khám phá các thiết bị công nghệ và thêm sản phẩm bạn yêu thích.</p>
        <a href="{{ ts_route('products.index') }}" class="btn btn-primary px-4 rounded-pill mx-auto">Khám phá sản phẩm</a>
    </div>
@else
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="d-flex justify-content-between align-items-center p-3 border-bottom bg-light">
                <strong><i class="bi bi-bag-check text-primary me-2"></i>{{ $summary['count'] }} sản phẩm</strong>
                <span class="small text-secondary">Giá được xác nhận lại khi đặt hàng</span>
            </div>
            @foreach($items as $item)
                @php
                    $product = $item->product;
                    $unitPrice = $cartService->unitPrice($item);
                    $lineTotal = $unitPrice * $item->quantity;
                    $image = $product && $product->thumbnail ? asset(ltrim($product->thumbnail, '/')) : null;
                @endphp
                <div class="p-3 border-bottom">
                    <div class="row g-3 align-items-center">
                        <div class="col-3 col-md-2">
                            <div class="rounded-3 bg-light d-flex align-items-center justify-content-center p-2" style="height:112px">
                                @if($image)
                                    <img src="{{ $image }}" alt="{{ $product->name }}" class="img-fluid" style="max-height:96px;object-fit:contain">
                                @else
                                    <i class="bi bi-image text-secondary fs-1"></i>
                                @endif
                            </div>
                        </div>
                        <div class="col-9 col-md-5">
                            <h6 class="fw-bold mb-1">{{ $product->name ?? 'Sản phẩm không còn tồn tại' }}</h6>
                            @if($item->variant)
                                <div class="small text-secondary mb-1">Phân loại: {{ $item->variant->variant_name }}</div>
                            @endif
                            <div class="text-primary fw-bold">{{ $money($unitPrice) }}</div>
                            <div class="small text-secondary mt-1">Tồn hiện tại:
                                {{ $item->variant ? $item->variant->stock_quantity : ($product->stock_quantity ?? 0) }}
                            </div>
                        </div>
                        <div class="col-7 col-md-3">
                            <form action="{{ route('cart.update', $item->id) }}" method="POST" class="d-flex align-items-center gap-2">
                                @csrf
                                @method('PATCH')
                                <label class="small text-secondary d-none d-md-inline">SL</label>
                                <input type="number" name="quantity" min="1" max="99" value="{{ $item->quantity }}" class="form-control form-control-sm text-center" style="max-width:82px">
                                <button class="btn btn-sm btn-outline-primary" title="Cập nhật số lượng"><i class="bi bi-arrow-repeat"></i></button>
                            </form>
                            <form action="{{ route('cart.remove', $item->id) }}" method="POST" class="mt-2">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-link btn-sm text-danger p-0 text-decoration-none"><i class="bi bi-trash me-1"></i>Xóa</button>
                            </form>
                        </div>
                        <div class="col-5 col-md-2 text-end">
                            <div class="small text-secondary">Thành tiền</div>
                            <strong>{{ $money($lineTotal) }}</strong>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 position-sticky" style="top:100px">
            <h5 class="fw-bold mb-3">Mã giảm giá</h5>
            @if($summary['coupon'])
                <div class="alert alert-success d-flex align-items-center justify-content-between py-2">
                    <span><i class="bi bi-ticket-perforated me-1"></i><strong>{{ $summary['coupon']->code }}</strong></span>
                    <form action="{{ route('cart.coupon.remove') }}" method="POST">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-link text-danger">Bỏ mã</button>
                    </form>
                </div>
            @else
                <form action="{{ route('cart.coupon.apply') }}" method="POST" class="input-group mb-4">
                    @csrf
                    <input type="text" name="coupon_code" class="form-control text-uppercase" placeholder="Nhập mã giảm giá" required>
                    <button class="btn btn-outline-primary">Áp dụng</button>
                </form>
            @endif

            <h5 class="fw-bold mb-3">Tóm tắt đơn hàng</h5>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary">Tạm tính</span><span>{{ $money($summary['subtotal']) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-secondary">Giảm giá</span><span class="text-success">-{{ $money($summary['discount']) }}</span>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <span class="text-secondary">Phí vận chuyển</span><span>{{ $summary['shipping'] ? $money($summary['shipping']) : 'Miễn phí' }}</span>
            </div>
            <hr>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <strong>Tổng thanh toán</strong><strong class="fs-4 text-primary">{{ $money($summary['total']) }}</strong>
            </div>
            <a href="{{ route('checkout.index') }}" class="btn btn-primary btn-lg w-100 rounded-3">
                Tiến hành thanh toán <i class="bi bi-arrow-right ms-1"></i>
            </a>
            <div class="small text-secondary mt-3"><i class="bi bi-shield-check text-success me-1"></i>Giá và tồn kho được kiểm tra lại trong lúc tạo đơn.</div>
        </div>
    </div>
</div>
@endif
@endsection
