@extends('layouts.app')

@section('title', 'Hồ sơ & Sổ địa chỉ - TechStore B2C')

@section('content')
@php
    $roleLabels = ['customer' => 'Khách hàng', 'staff' => 'Nhân viên', 'admin' => 'Quản trị viên'];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <span class="text-primary fw-semibold small text-uppercase">Tài khoản của tôi</span>
        <h1 class="h3 fw-bold mb-0">Hồ sơ cá nhân & Sổ địa chỉ</h1>
    </div>
    <span class="badge bg-primary-subtle text-primary fs-6">
        <i class="bi bi-person-badge me-1"></i>{{ $roleLabels[$user->role] ?? $user->role }}
    </span>
</div>

<div class="row g-4">
    {{-- Cột trái: Thông tin cá nhân + Đổi mật khẩu --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-person-vcard text-primary me-2"></i>Thông tin cá nhân</h5>
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Họ và tên *</label>
                    <input type="text" name="name" maxlength="120" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Email đăng nhập *</label>
                    <input type="email" name="email" maxlength="150" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Số điện thoại</label>
                    <input type="text" name="phone" maxlength="25" class="form-control" value="{{ old('phone', $user->phone) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Địa chỉ mặc định nhanh</label>
                    <textarea name="address" rows="2" maxlength="500" class="form-control">{{ old('address', $user->address) }}</textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-check2 me-1"></i>Lưu thông tin
                </button>
            </form>
        </div>

        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-lock text-primary me-2"></i>Đổi mật khẩu</h5>
            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Mật khẩu hiện tại *</label>
                    <input type="password" name="current_password" autocomplete="current-password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Mật khẩu mới *</label>
                    <input type="password" name="password" minlength="8" maxlength="72" autocomplete="new-password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nhập lại mật khẩu mới *</label>
                    <input type="password" name="password_confirmation" minlength="8" maxlength="72" autocomplete="new-password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-outline-primary btn-sm px-4">
                    <i class="bi bi-shield-check me-1"></i>Cập nhật mật khẩu
                </button>
            </form>
        </div>
    </div>

    {{-- Cột phải: Sổ địa chỉ giao hàng --}}
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-geo-alt text-primary me-2"></i>Sổ địa chỉ giao hàng ({{ $addresses->count() }})</h5>
                <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#addAddressForm">
                    <i class="bi bi-plus-lg me-1"></i>Thêm địa chỉ mới
                </button>
            </div>

            {{-- Form thêm mới (thu gọn) --}}
            <div class="collapse {{ $addresses->isEmpty() ? 'show' : '' }} mb-4" id="addAddressForm">
                <form method="POST" action="{{ route('profile.addresses.store') }}" class="border rounded-4 p-3 bg-light">
                    @csrf
                    <h6 class="fw-bold text-primary mb-3">Thêm địa chỉ giao hàng</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Người nhận *</label>
                            <input type="text" name="recipient_name" maxlength="120" class="form-control form-control-sm" value="{{ old('recipient_name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Số điện thoại *</label>
                            <input type="text" name="recipient_phone" maxlength="25" class="form-control form-control-sm" value="{{ old('recipient_phone', $user->phone) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tỉnh / Thành phố *</label>
                            <input type="text" name="province" maxlength="100" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Quận / Huyện *</label>
                            <input type="text" name="district" maxlength="100" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Phường / Xã</label>
                            <input type="text" name="ward" maxlength="100" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Địa chỉ cụ thể (số nhà, đường) *</label>
                            <input type="text" name="street_address" maxlength="255" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check small">
                                <input class="form-check-input" type="checkbox" name="is_default" value="1" id="new_addr_default">
                                <label class="form-check-label" for="new_addr_default">Đặt làm địa chỉ mặc định</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm mt-3">Lưu địa chỉ</button>
                </form>
            </div>

            @forelse($addresses as $addr)
                <div class="border rounded-4 p-3 mb-3 {{ $addr->is_default ? 'border-primary bg-primary-subtle bg-opacity-10' : '' }}">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <strong>{{ $addr->recipient_name }}</strong>
                            <span class="text-muted mx-1">•</span>
                            <span>{{ $addr->recipient_phone }}</span>
                            @if($addr->is_default)
                                <span class="badge bg-primary ms-2">Mặc định</span>
                            @endif
                            <div class="small text-secondary mt-1">{{ $addr->full_address }}</div>
                        </div>
                        <div class="d-flex gap-1">
                            @if(! $addr->is_default)
                                <form method="POST" action="{{ route('profile.addresses.default', $addr) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-outline-primary btn-sm" type="submit">Đặt mặc định</button>
                                </form>
                            @endif
                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#editAddr{{ $addr->id }}">Sửa</button>
                            <form method="POST" action="{{ route('profile.addresses.destroy', $addr) }}" onsubmit="return confirm('Xóa địa chỉ này?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>

                    <div class="collapse mt-3" id="editAddr{{ $addr->id }}">
                        <form method="POST" action="{{ route('profile.addresses.update', $addr) }}" class="border-top pt-3">
                            @csrf @method('PUT')
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small">Người nhận *</label>
                                    <input type="text" name="recipient_name" maxlength="120" class="form-control form-control-sm" value="{{ $addr->recipient_name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small">Số điện thoại *</label>
                                    <input type="text" name="recipient_phone" maxlength="25" class="form-control form-control-sm" value="{{ $addr->recipient_phone }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Tỉnh / TP *</label>
                                    <input type="text" name="province" maxlength="100" class="form-control form-control-sm" value="{{ $addr->province }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Quận / Huyện *</label>
                                    <input type="text" name="district" maxlength="100" class="form-control form-control-sm" value="{{ $addr->district }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small">Phường / Xã</label>
                                    <input type="text" name="ward" maxlength="100" class="form-control form-control-sm" value="{{ $addr->ward }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small">Địa chỉ cụ thể *</label>
                                    <input type="text" name="street_address" maxlength="255" class="form-control form-control-sm" value="{{ $addr->street_address }}" required>
                                </div>
                                <div class="col-12">
                                    <div class="form-check small">
                                        <input class="form-check-input" type="checkbox" name="is_default" value="1" id="def_{{ $addr->id }}" @checked($addr->is_default)>
                                        <label class="form-check-label" for="def_{{ $addr->id }}">Đặt làm địa chỉ mặc định</label>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm mt-2">Cập nhật địa chỉ</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-muted small mb-0">Bạn chưa lưu địa chỉ giao hàng nào. Địa chỉ đầu tiên sẽ tự động được chọn làm mặc định khi thanh toán.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
