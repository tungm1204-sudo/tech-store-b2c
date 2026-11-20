@extends('layouts.app')

@section('content')
<h1>{{ $product->name }}</h1>

@if ($product->thumbnail)
    <img src="{{ asset($product->thumbnail) }}" alt="{{ $product->name }}" width="200">
@else
    <div style="width:200px;height:200px;background:#eee;"></div>
@endif

<p>Thương hiệu: {{ $product->brand->name ?? '' }}</p>
<p>Danh mục: {{ $product->category->name ?? '' }}</p>

<p>
    <strong>{{ number_format($product->effective_price, 0, ',', '.') }} đ</strong>
    @if ($product->discount_percent > 0)
        <del>{{ number_format($product->price, 0, ',', '.') }} đ</del>
        (-{{ $product->discount_percent }}%)
    @endif
</p>

<p>{{ $product->stock_quantity > 0 ? 'Còn hàng: ' . $product->stock_quantity : 'Hết hàng' }}</p>

<p>{{ $product->description }}</p>

<h3>Thông số kỹ thuật</h3>
<table>
    <tr><td>CPU</td><td>{{ $product->cpu }}</td></tr>
    <tr><td>RAM</td><td>{{ $product->ram }}</td></tr>
    <tr><td>ROM</td><td>{{ $product->rom }}</td></tr>
    <tr><td>GPU</td><td>{{ $product->gpu }}</td></tr>
    <tr><td>Màn hình</td><td>{{ $product->screen }}</td></tr>
    <tr><td>Pin</td><td>{{ $product->battery }}</td></tr>
</table>

<a href="{{ route('products.index') }}">Quay lại danh sách</a>
@endsection