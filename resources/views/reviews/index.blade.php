@extends('layouts.app')

@section('title', 'Đánh giá ' . $product->name . ' - TechStore B2C')

@section('content')
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="{{ ts_route('products.show', $product->slug) }}">{{ $product->name }}</a></li>
        <li class="breadcrumb-item active">Đánh giá</li>
    </ol>
</nav>

<div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="small text-muted">{{ $product->brand?->name }} • {{ $product->category?->name }}</div>
            <h3 class="fw-bold mb-1">{{ $product->name }}</h3>
            <div class="fs-5 fw-bold text-danger">{{ number_format($product->sale_price ?: $product->price, 0, ',', '.') }}₫</div>
        </div>
        <a href="{{ ts_route(['products.show', 'home'], $product->slug) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-arrow-left me-1"></i>Về trang sản phẩm</a>
    </div>
</div>

@include('reviews._section', ['product' => $product])
@endsection
