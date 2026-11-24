@extends('layouts.admin')

@section('page_title', 'Quản lý danh mục')

@section('content')
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h5 class="fw-bold mb-1">Danh sách danh mục</h5>
                <small class="text-muted">Tổng {{ $categories->total() }} danh mục</small>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>Thêm danh mục
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tên danh mục</th>
                        <th>Slug</th>
                        <th class="text-center">Số sản phẩm</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($categories as $category)
                    <tr>
                        <td class="fw-semibold">
                            <i class="bi {{ $category->icon ?? 'bi-box' }} text-primary me-2"></i>{{ $category->name }}
                        </td>
                        <td><code>{{ $category->slug }}</code></td>
                        <td class="text-center">
                            <span class="badge rounded-pill bg-light text-dark border">{{ $category->products_count }}</span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Sửa
                            </a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-inline"
                                  onsubmit="return confirm('Xóa danh mục này?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-muted py-4">Chưa có danh mục nào.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $categories->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection