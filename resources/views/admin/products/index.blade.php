@extends('layouts.admin')

@section('page_title', 'Quản lý sản phẩm')

@section('content')
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <h5 class="fw-bold mb-1">Danh sách sản phẩm</h5>
                <small class="text-muted">Tổng {{ $products->total() }} sản phẩm</small>
            </div>
            <div class="d-flex gap-2">
                <form method="GET" action="{{ route('admin.products.index') }}" class="input-group" style="width:280px">
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Tìm theo tên...">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                </form>
                <a href="{{ route('admin.products.create') }}" class="btn btn-primary text-nowrap">
                    <i class="bi bi-plus-lg me-1"></i>Thêm sản phẩm
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Thương hiệu</th>
                        <th>Danh mục</th>
                        <th class="text-end">Giá</th>
                        <th class="text-center">Tồn kho</th>
                        <th class="text-center">Trạng thái</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($products as $product)
                    <tr class="{{ $product->is_active ? '' : 'opacity-50' }}">
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                @if ($product->thumbnail)
                                    <img src="{{ asset($product->thumbnail) }}" alt="" width="44" height="44"
                                         class="rounded-3 bg-light p-1">
                                @endif
                                <div>
                                    <div class="fw-semibold">{{ $product->name }}</div>
                                    <small class="text-muted">SKU: {{ $product->sku }}</small>
                                </div>
                            </div>
                        </td>
                        <td>{{ $product->brand->name ?? '—' }}</td>
                        <td><span class="badge bg-info-subtle text-info-emphasis">{{ $product->category->name ?? '—' }}</span></td>
                        <td class="text-end fw-semibold">
                            {{ number_format($product->price, 0, ',', '.') }} đ
                        </td>
                        <td class="text-center">
                            @if ($product->isLowStock())
                                <span class="badge bg-danger-subtle text-danger-emphasis">{{ $product->stock_quantity }} · sắp hết</span>
                            @else
                                <span class="badge bg-success-subtle text-success-emphasis">{{ $product->stock_quantity }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($product->is_active)
                                <span class="badge rounded-pill bg-success">Đang bán</span>
                            @else
                                <span class="badge rounded-pill bg-secondary">Đã ẩn</span>
                            @endif
                        </td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i> Sửa
                            </a>
                            @if ($product->is_active)
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline"
                                      onsubmit="return confirm('Ẩn sản phẩm này khỏi trang khách?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-eye-slash"></i> Ẩn
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có sản phẩm nào.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $products->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection