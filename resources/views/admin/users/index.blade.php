@extends('layouts.admin')

@section('title', 'Quản lý Tài khoản & RBAC')
@section('page_title', 'Quản lý Tài khoản & Phân quyền RBAC')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card kpi-card p-3">
            <div class="small text-muted">Tổng tài khoản</div>
            <div class="fs-4 fw-bold text-primary">{{ $summary['total'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card p-3">
            <div class="small text-muted">Admin / Staff</div>
            <div class="fs-4 fw-bold text-dark">{{ $summary['admin'] }} / {{ $summary['staff'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card p-3">
            <div class="small text-muted">Khách hàng</div>
            <div class="fs-4 fw-bold text-success">{{ $summary['customer'] }}</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card kpi-card p-3">
            <div class="small text-muted">Đang tạm khóa</div>
            <div class="fs-4 fw-bold text-danger">{{ $summary['locked'] }}</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 mb-4">
            <div class="col-md-5">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm theo tên, email hoặc SĐT...">
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">Tất cả vai trò</option>
                    @foreach($roleLabels as $key => $label)
                        <option value="{{ $key }}" @selected(request('role') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Mọi trạng thái</option>
                    <option value="active" @selected(request('status') === 'active')>Hoạt động</option>
                    <option value="locked" @selected(request('status') === 'locked')>Đã khóa</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Lọc</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Người dùng</th>
                        <th class="text-center">Đơn / Đánh giá</th>
                        <th>Vai trò (RBAC)</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($users as $u)
                    <tr class="{{ $u->is_active ? '' : 'table-secondary' }}">
                        <td class="text-muted">#{{ $u->id }}</td>
                        <td>
                            <div class="fw-semibold">{{ $u->name }} @if($u->id === auth()->id()) <span class="badge bg-info text-dark">Bạn</span> @endif</div>
                            <div class="small text-muted">{{ $u->email }} @if($u->phone) • {{ $u->phone }} @endif</div>
                        </td>
                        <td class="text-center small">
                            <span class="badge bg-light text-dark border">{{ $u->orders_count }} đơn</span>
                            <span class="badge bg-light text-dark border">{{ $u->reviews_count }} review</span>
                        </td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.role', $u) }}" class="d-flex gap-1 align-items-center">
                                @csrf @method('PATCH')
                                <select name="role" class="form-select form-select-sm" style="width: 145px;" @disabled($u->id === auth()->id())>
                                    @foreach($roleLabels as $key => $label)
                                        <option value="{{ $key }}" @selected($u->role === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if($u->id !== auth()->id())
                                    <button class="btn btn-sm btn-outline-primary" type="submit" title="Lưu vai trò"><i class="bi bi-check2"></i></button>
                                @endif
                            </form>
                        </td>
                        <td class="text-center">
                            @if($u->is_active)
                                <span class="badge bg-success">Hoạt động</span>
                            @else
                                <span class="badge bg-danger">Đã khóa</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($u->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.toggle_status', $u) }}" class="d-inline"
                                      onsubmit="return confirm('{{ $u->is_active ? 'Khóa' : 'Mở khóa' }} tài khoản {{ $u->email }}?')">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-sm {{ $u->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" type="submit">
                                        <i class="bi {{ $u->is_active ? 'bi-lock' : 'bi-unlock' }} me-1"></i>{{ $u->is_active ? 'Khóa' : 'Mở khóa' }}
                                    </button>
                                </form>
                            @else
                                <span class="small text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Không tìm thấy tài khoản nào.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $users->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection
