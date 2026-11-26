@extends('layouts.app')

@section('title', 'Đăng ký tài khoản - TechStore B2C')

@section('content')
<div class="row justify-content-center my-4">
    <div class="col-md-7 col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5">
            <div class="text-center mb-4">
                <span class="bg-primary text-white rounded-3 px-3 py-2 d-inline-block mb-2"><i class="bi bi-person-plus-fill fs-4"></i></span>
                <h1 class="h4 fw-bold mb-1">Tạo tài khoản mới</h1>
                <p class="small text-muted mb-0">Tài khoản đăng ký mới luôn được cấp quyền Khách hàng (Customer).</p>
            </div>

            <form method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold small">Họ và tên *</label>
                    <input id="name" name="name" type="text" maxlength="120" autocomplete="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name') }}" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small">Email *</label>
                    <input id="email" name="email" type="email" maxlength="150" autocomplete="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}" required>
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label fw-semibold small">Số điện thoại</label>
                    <input id="phone" name="phone" type="text" maxlength="25" autocomplete="tel"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone') }}" placeholder="VD: 0912345678">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small">Mật khẩu *</label>
                    <input id="password" name="password" type="password" minlength="8" maxlength="72" autocomplete="new-password"
                           class="form-control @error('password') is-invalid @enderror" required>
                    <div class="form-text">Từ 8 đến 72 ký tự (tối đa 72 byte).</div>
                </div>

                <div class="mb-4">
                    <label for="password_confirmation" class="form-label fw-semibold small">Nhập lại mật khẩu *</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" maxlength="72" autocomplete="new-password"
                           class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                    <i class="bi bi-check2-circle me-1"></i>Đăng ký tài khoản
                </button>
            </form>

            <p class="text-center small text-muted mt-4 mb-0">
                Đã có tài khoản? <a href="{{ route('login') }}" class="fw-semibold text-decoration-none">Đăng nhập</a>
            </p>
        </div>
    </div>
</div>
@endsection
