@extends('layouts.app')

@section('title', 'Quên mật khẩu - TechStore B2C')

@section('content')
<div class="row justify-content-center my-4">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <h1 class="h4 fw-bold mb-2"><i class="bi bi-key text-primary me-2"></i>Quên mật khẩu</h1>
            <p class="small text-muted mb-4">Nhập email đã đăng ký. Hệ thống sẽ tạo liên kết đặt lại mật khẩu có hiệu lực trong 60 phút (chỉ sử dụng một lần).</p>

            @if(session('demo_reset_url'))
                <div class="alert alert-info small">
                    <strong>[Môi trường Local - Kiểm thử không cần SMTP]</strong><br>
                    Liên kết đặt lại mật khẩu:<br>
                    <a href="{{ session('demo_reset_url') }}" class="text-break fw-semibold">{{ session('demo_reset_url') }}</a>
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small">Địa chỉ email *</label>
                    <input id="email" name="email" type="email" maxlength="150" autocomplete="email"
                           class="form-control" value="{{ old('email') }}" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">
                    <i class="bi bi-envelope-arrow-up me-1"></i>Gửi liên kết đặt lại
                </button>
            </form>

            <p class="text-center small text-muted mt-4 mb-0">
                <a href="{{ route('login') }}" class="text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Quay lại đăng nhập</a>
            </p>
        </div>
    </div>
</div>
@endsection
