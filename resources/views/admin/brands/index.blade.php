@extends('layouts.admin')

@section('page_title', 'Quản lý thương hiệu')

@section('content')
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">Danh sách thương hiệu</h5>
                <small class="text-muted">Tổng {{ $brands->total() }} thương hiệu</small>
            </div>
            <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Thêm thương hiệu
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tên thương hiệu</th>
                        <th>Slug</th>
                        <th>Quốc gia</th>
                        <th class="text-center">Số sản phẩm</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($brands as $brand)
                    <tr>
                        <td class="fw-semibold">{{ $brand->name }}</td>
                        <td><code>{{ $brand->slug }}</code></td>
                        <td>{{ $brand->country }}</td>
                        <td class="text-center">
                            <span class="badge rounded-pill bg-light text-dark border">{{ $brand->products_count }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Sửa
                            </a>
                            <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" class="d-inline"
                                  onsubmit="return confirm('Xóa thương hiệu này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Chưa có thương hiệu nào.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $brands->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection