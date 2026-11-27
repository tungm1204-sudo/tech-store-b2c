@extends('layouts.app')

@section('title', 'Đặt lại mật khẩu - TechStore B2C')

@section('content')
<div class="row justify-content-center my-4">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <h1 class="h4 fw-bold mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Đặt lại mật khẩu</h1>

            @if(! $tokenValid)
                <div class="alert alert-danger small">
                    Liên kết đặt lại mật khẩu không hợp lệ, đã hết hạn (quá 60 phút) hoặc đã được sử dụng.
                </div>
                <a href="{{ route('password.request') }}" class="btn btn-primary w-100">Yêu cầu liên kết mới</a>
            @else
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold small">Email</label>
                        <input id="email" name="email" type="email" class="form-control bg-light" value="{{ old('email', $email) }}" readonly required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold small">Mật khẩu mới *</label>
                        <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password" class="form-control" required autofocus>
                    </div>

                    <div class="mb-4">
                        <label for="password_confirmation" class="form-label fw-semibold small">Nhập lại mật khẩu mới *</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        <i class="bi bi-check2-circle me-1"></i>Lưu mật khẩu mới
                    </button>
                </form>
            @endif

            <p class="text-center small text-muted mt-4 mb-0">
                <a href="{{ route('login') }}" class="text-decoration-none">Quay lại đăng nhập</a>
            </p>
        </div>
    </div>
</div>
@endsection
