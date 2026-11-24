@extends('layouts.app')

@section('content')
<h1>Quản lý thương hiệu</h1>

@if (session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif
@if (session('error'))
    <p style="color:red">{{ session('error') }}</p>
@endif

<a href="{{ route('admin.brands.create') }}">+ Thêm thương hiệu</a>

<table border="1" cellpadding="6">
    <tr>
        <th>Tên</th>
        <th>Slug</th>
        <th>Quốc gia</th>
        <th>Số sản phẩm</th>
        <th>Thao tác</th>
    </tr>
    @forelse ($brands as $brand)
        <tr>
            <td>{{ $brand->name }}</td>
            <td>{{ $brand->slug }}</td>
            <td>{{ $brand->country }}</td>
            <td>{{ $brand->products_count }}</td>
            <td>
                <a href="{{ route('admin.brands.edit', $brand) }}">Sửa</a>
                <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}"
                      style="display:inline" onsubmit="return confirm('Xóa thương hiệu này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Xóa</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5">Chưa có thương hiệu nào.</td></tr>
    @endforelse
</table>

{{ $brands->links() }}
@endsection