@extends('layouts.app')

@section('title', 'Thanh toán VietQR | TechStore B2C')

@section('content')
@php $money = number_format((float) $order->total_amount, 0, ',', '.') . '₫'; @endphp
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="p-4 text-white" style="background:linear-gradient(120deg,#0f172a,#1d4ed8,#0284c7)">
                <div class="small text-white-50">THANH TOÁN ĐƠN HÀNG</div>
                <h1 class="h3 fw-bold mb-1">Quét mã VietQR để chuyển khoản</h1>
                <div class="text-white-50">Mã đơn: {{ $order->order_code }}</div>
            </div>
            <div class="p-4 p-md-5">
                <div class="row g-4 align-items-center">
                    <div class="col-md-6 text-center">
                        @if($qrUrl)
                            <div class="bg-white border rounded-4 p-3 d-inline-block shadow-sm">
                                <img src="{{ $qrUrl }}" alt="VietQR thanh toán đơn {{ $order->order_code }}" class="img-fluid" style="width:300px;max-width:100%">
                            </div>
                            <p class="small text-secondary mt-3 mb-0">Mã QR chứa sẵn số tiền và nội dung chuyển khoản riêng của đơn này.</p>
                        @else
                            <div class="alert alert-warning text-start">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Chưa cấu hình tài khoản nhận VietQR. Hãy điền <code>VIETQR_BANK_ID</code>, <code>VIETQR_ACCOUNT_NO</code> và <code>VIETQR_ACCOUNT_NAME</code> trong file <code>.env</code>, sau đó chạy <code>php artisan config:clear</code>.
                            </div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small">Số tiền cần chuyển</div>
                        <div class="display-6 fw-bold text-primary mb-3">{{ $money }}</div>
                        <div class="border rounded-3 p-3 mb-3">
                            <div class="small text-secondary">Nội dung chuyển khoản</div>
                            <div class="fw-bold fs-5">{{ 'TECHSTORE ' . $order->order_code }}</div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="navigator.clipboard.writeText(@js('TECHSTORE ' . $order->order_code))">
                                <i class="bi bi-clipboard me-1"></i>Sao chép nội dung
                            </button>
                        </div>
                        <div class="d-flex justify-content-between mb-2"><span class="text-secondary">Trạng thái</span><span class="badge bg-warning text-dark">{{ $order->payment_status === 'paid' ? 'Đã thanh toán' : 'Chờ thanh toán' }}</span></div>
                        <ol class="small text-secondary ps-3">
                            <li>Mở ứng dụng ngân hàng và quét mã QR.</li>
                            <li>Kiểm tra đúng tên người nhận, số tiền và nội dung.</li>
                            <li>Hoàn tất chuyển khoản, giữ lại biên nhận.</li>
                        </ol>
                        @if(app()->environment('local') && $order->payment_status !== 'paid')
                            <form action="{{ route('checkout.mock-paid', $order) }}" method="POST" class="mt-3">
                                @csrf
                                <button class="btn btn-success w-100" onclick="return confirm('Đây là thao tác mô phỏng. Xác nhận đánh dấu đơn đã thanh toán?')">
                                    <i class="bi bi-check-circle me-1"></i>Mô phỏng xác nhận đã chuyển khoản
                                </button>
                            </form>
                        @endif
                        <a href="{{ route('checkout.result', $order) }}" class="btn btn-outline-secondary w-100 mt-2">Xem trạng thái đơn hàng</a>
                    </div>
                </div>
                <div class="alert alert-info mt-4 mb-0 small">
                    Đây là QR động VietQR Quick Link. Hệ thống chưa tự đối soát tiền vào ngân hàng; nút xác nhận phía trên chỉ hiển thị trong môi trường local để trình diễn. Thanh toán thật cần API/webhook từ ngân hàng hoặc đơn vị trung gian.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
