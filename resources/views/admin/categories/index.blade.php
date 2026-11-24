@extends('layouts.app')

@section('content')
<h1>Quản lý danh mục</h1>

@if (session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif
@if (session('error'))
    <p style="color:red">{{ session('error') }}</p>
@endif

<a href="{{ route('admin.categories.create') }}">+ Thêm danh mục</a>

<table border="1" cellpadding="6">
    <tr>
        <th>Tên</th>
        <th>Slug</th>
        <th>Số sản phẩm</th>
        <th>Thao tác</th>
    </tr>
    @forelse ($categories as $category)
        <tr>
            <td>{{ $category->name }}</td>
            <td>{{ $category->slug }}</td>
            <td>{{ $category->products_count }}</td>
            <td>
                <a href="{{ route('admin.categories.edit', $category) }}">Sửa</a>
                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                      style="display:inline" onsubmit="return confirm('Xóa danh mục này?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Xóa</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="4">Chưa có danh mục nào.</td></tr>
    @endforelse
</table>

{{ $categories->links() }}
@endsection