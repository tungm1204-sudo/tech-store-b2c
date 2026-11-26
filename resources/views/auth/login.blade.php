@extends('layouts.app')

@section('title', 'Đăng nhập - TechStore B2C')

@section('content')
<div class="row justify-content-center my-4">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="bg-primary text-white rounded-3 px-3 py-2 d-inline-block mb-2"><i class="bi bi-shield-lock-fill fs-4"></i></span>
                <h1 class="h4 fw-bold mb-1">Đăng nhập TechStore</h1>
                <p class="small text-muted mb-0">Đăng nhập để theo dõi đơn hàng, quản lý sổ địa chỉ và đánh giá sản phẩm.</p>
            </div>

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small">Email</label>
                    <input id="email" name="email" type="email" maxlength="150" autocomplete="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <label for="password" class="form-label fw-semibold small">Mật khẩu</label>
                        <a href="{{ route('password.request') }}" class="small text-decoration-none">Quên mật khẩu?</a>
                    </div>
                    <input id="password" name="password" type="password" autocomplete="current-password"
                           class="form-control" required>
                </div>

                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember" @checked(old('remember'))>
                    <label class="form-check-label small" for="remember">Ghi nhớ đăng nhập</label>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Đăng nhập
                </button>
            </form>

            <p class="text-center small text-muted mt-4 mb-0">
                Chưa có tài khoản? <a href="{{ route('register') }}" class="fw-semibold text-decoration-none">Đăng ký ngay</a>
            </p>

            @if(app()->environment('local') && Route::has('dev.login'))
                <hr class="my-4">
                <div class="text-center">
                    <div class="small text-muted mb-2"><i class="bi bi-lightning-charge-fill text-warning me-1"></i>Đăng nhập nhanh kiểm thử (chỉ APP_ENV=local)</div>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <a href="{{ route('dev.login', 'admin') }}" class="btn btn-sm btn-outline-dark">Admin</a>
                        <a href="{{ route('dev.login', 'staff') }}" class="btn btn-sm btn-outline-secondary">Staff</a>
                        <a href="{{ route('dev.login', 'customer') }}" class="btn btn-sm btn-outline-primary">Customer</a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
