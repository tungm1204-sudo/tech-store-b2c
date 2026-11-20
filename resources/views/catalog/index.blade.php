@extends('layouts.app')

@section('content')
<h1>Sản phẩm</h1>

<form method="GET" action="{{ route('products.index') }}">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="Tìm sản phẩm">
    <input type="number" name="min" value="{{ request('min') }}" placeholder="Giá từ">
    <input type="number" name="max" value="{{ request('max') }}" placeholder="Giá đến">
    
    <select name="category">
        <option value="">Tất cả danh mục</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>

    <select name="usage">
        <option value="">Mọi nhu cầu</option>
        @foreach ($usageOptions as $value => $label)
            <option value="{{ $value }}" @selected(request('usage') == $value)>{{ $label }}</option>
        @endforeach
    </select>

    <select name="ram">
        <option value="">Mọi RAM</option>
        @foreach ($ramOptions as $ram)
            <option value="{{ $ram }}" @selected(request('ram') == $ram)>{{ $ram }}</option>
        @endforeach
    </select>

    <select name="storage">
        <option value="">Mọi dung lượng</option>
        @foreach ($storageOptions as $storage)
            <option value="{{ $storage }}" @selected(request('storage') == $storage)>{{ $storage }}</option>
        @endforeach
    </select>

    <label>
        <input type="checkbox" name="ssd" value="1" @checked(request('ssd'))>
        Chỉ SSD/NVMe
    </label>

    <select name="color">
        <option value="">Mọi màu</option>
        @foreach ($colorOptions as $color)
            <option value="{{ $color }}" @selected(request('color') == $color)>{{ $color }}</option>
        @endforeach
    </select>

    @foreach ($brands as $brand)
        <label>
            <input type="checkbox" name="brand[]" value="{{ $brand->id }}"
                @checked(in_array($brand->id, (array) request('brand', [])))>
            {{ $brand->name }}
        </label>
    @endforeach

    <button type="submit">Lọc</button>
</form>

<div class="grid">
    @forelse ($products as $product)
        <a href="{{ route('products.show', $product->slug) }}">
            @if ($product->thumbnail)
                <img src="{{ asset($product->thumbnail) }}" alt="{{ $product->name }}" width="200">
            @endif
            <h3>{{ $product->name }}</h3>
            <p>{{ $product->brand->name ?? '' }}</p>
            <p>{{ number_format($product->effective_price, 0, ',', '.') }} đ</p>
        </a>
    @empty
        <p>Không tìm thấy sản phẩm nào.</p>
    @endforelse
</div>

{{ $products->links() }}
@endsection