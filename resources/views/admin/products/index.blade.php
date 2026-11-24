@extends('layouts.app')

@section('content')
<h1>Quản lý sản phẩm</h1>

@if (session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

<a href="{{ route('admin.products.create') }}">+ Thêm sản phẩm</a>

<form method="GET" action="{{ route('admin.products.index') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm theo tên">
    <button type="submit">Tìm</button>
</form>

<table border="1" cellpadding="6">
    <tr>
        <th>Tên</th>
        <th>Thương hiệu</th>
        <th>Danh mục</th>
        <th>Giá</th>
        <th>Tồn kho</th>
        <th>Trạng thái</th>
        <th>Thao tác</th>
    </tr>
    @forelse ($products as $product)
        <tr>
            <td>{{ $product->name }}</td>
            <td>{{ $product->brand->name ?? '' }}</td>
            <td>{{ $product->category->name ?? '' }}</td>
            <td>{{ number_format($product->price, 0, ',', '.') }} đ</td>
            <td>{{ $product->stock_quantity }}</td>
            <td>{{ $product->is_active ? 'Đang bán' : 'Đã ẩn' }}</td>
            <td>
                <a href="{{ route('admin.products.edit', $product) }}">Sửa</a>
                @if ($product->is_active)
                    <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                          style="display:inline" onsubmit="return confirm('Ẩn sản phẩm này?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Ẩn</button>
                    </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="7">Chưa có sản phẩm nào.</td></tr>
    @endforelse
</table>

{{ $products->links() }}
@endsection