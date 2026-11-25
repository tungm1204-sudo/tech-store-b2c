@extends('layouts.app')

@section('title', 'Thanh toán | TechStore B2C')

@section('content')
@php
    $money = fn ($value) => number_format((float) $value, 0, ',', '.') . '₫';
    $user = auth()->user();
    $defaultAddress = $addresses->firstWhere('is_default', true) ?? $addresses->first();
@endphp

<div class="mb-4">
    <span class="text-primary fw-semibold small text-uppercase">Giỏ hàng / Thanh toán</span>
    <h1 class="fw-bold mb-1">Thông tin thanh toán</h1>
    <p class="text-secondary mb-0">Vui lòng kiểm tra người nhận, địa chỉ và phương thức thanh toán.</p>
</div>

<form action="{{ route('checkout.store') }}" method="POST">
    @csrf
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-truck text-primary me-2"></i>Thông tin nhận hàng</h5>
                @if($addresses->isNotEmpty())
                    <label class="form-label">Địa chỉ đã lưu</label>
                    <select class="form-select mb-3" id="savedAddress">
                        <option value="">-- Nhập địa chỉ khác --</option>
                        @foreach($addresses as $address)
                            <option value="{{ $address->full_address }}"
                                data-name="{{ $address->recipient_name }}"
                                data-phone="{{ $address->recipient_phone }}"
                                {{ $defaultAddress && $defaultAddress->id === $address->id ? 'selected' : '' }}>
                                {{ $address->recipient_name }} - {{ $address->full_address }}
                            </option>
                        @endforeach
                    </select>
                @endif

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Họ và tên người nhận *</label>
                        <input name="customer_name" id="customer_name" class="form-control" maxlength="120" required value="{{ old('customer_name', $defaultAddress->recipient_name ?? $user->name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Số điện thoại *</label>
                        <input name="customer_phone" id="customer_phone" class="form-control" maxlength="25" required value="{{ old('customer_phone', $defaultAddress->recipient_phone ?? $user->phone) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Email</label>
                        <input type="email" name="customer_email" class="form-control" maxlength="150" value="{{ old('customer_email', $user->email) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Địa chỉ giao hàng *</label>
                        <textarea name="shipping_address" id="shipping_address" class="form-control" rows="3" maxlength="1000" required>{{ old('shipping_address', $defaultAddress->full_address ?? $user->address) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Ghi chú cho cửa hàng</label>
                        <textarea name="note" class="form-control" rows="2" maxlength="2000" placeholder="Thời gian nhận hàng, lưu ý giao hàng...">{{ old('note') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-credit-card text-primary me-2"></i>Phương thức thanh toán</h5>
                <label class="border rounded-3 p-3 d-flex gap-3 align-items-start mb-3">
                    <input type="radio" name="payment_method" value="cod" class="form-check-input mt-1" {{ old("payment_method", "cod") === "cod" ? "checked" : "" }}>
                    <span>
                        <strong>Thanh toán khi nhận hàng (COD)</strong>
                        <span class="d-block small text-secondary">Thanh toán trực tiếp cho nhân viên giao hàng.</span>
                    </span>
                    <i class="bi bi-cash-coin fs-3 text-success ms-auto"></i>
                </label>
                <label class="border rounded-3 p-3 d-flex gap-3 align-items-start">
                    <input type="radio" name="payment_method" value="qr_transfer" class="form-check-input mt-1" {{ old('payment_method') === 'qr_transfer' ? 'checked' : '' }}>
                    <span>
                        <strong>Chuyển khoản ngân hàng bằng VietQR</strong>
                        <span class="d-block small text-secondary">Mã QR được tạo riêng theo số tiền và mã đơn hàng.</span>
                    </span>
                    <i class="bi bi-qr-code fs-3 text-primary ms-auto"></i>
                </label>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 position-sticky" style="top:100px">
                <h5 class="fw-bold mb-3">Đơn hàng của bạn</h5>
                @foreach($cart->items as $item)
                    @php $unit = $cartService->unitPrice($item); @endphp
                    <div class="d-flex justify-content-between gap-3 mb-3">
                        <div>
                            <div class="fw-semibold">{{ $item->product->name }}</div>
                            @if($item->variant)<div class="small text-secondary">{{ $item->variant->variant_name }}</div>@endif
                            <div class="small text-secondary">SL: {{ $item->quantity }}</div>
                        </div>
                        <strong class="text-nowrap">{{ $money($unit * $item->quantity) }}</strong>
                    </div>
                @endforeach
                <hr>
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Tạm tính</span><span>{{ $money($summary['subtotal']) }}</span></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Mã giảm giá</span><span class="text-success">-{{ $money($summary['discount']) }}</span></div>
                <div class="d-flex justify-content-between mb-3"><span class="text-secondary">Vận chuyển</span><span>{{ $summary['shipping'] ? $money($summary['shipping']) : 'Miễn phí' }}</span></div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-4"><strong>Tổng thanh toán</strong><strong class="fs-4 text-primary">{{ $money($summary['total']) }}</strong></div>
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-3">
                    Xác nhận đặt hàng <i class="bi bi-shield-check ms-1"></i>
                </button>
                <a href="{{ route('cart.index') }}" class="btn btn-link mt-2 text-decoration-none">Quay lại giỏ hàng</a>
                <p class="small text-secondary mt-2 mb-0"><i class="bi bi-lock-fill me-1"></i>Máy chủ kiểm tra lại giá và kho trong một giao dịch CSDL trước khi lưu đơn.</p>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('savedAddress');
    if (!select) return;
    select.addEventListener('change', function () {
        const option = select.options[select.selectedIndex];
        if (!option.value) return;
        document.getElementById('shipping_address').value = option.value;
        document.getElementById('customer_name').value = option.dataset.name || '';
        document.getElementById('customer_phone').value = option.dataset.phone || '';
    });
});
</script>
@endpush
