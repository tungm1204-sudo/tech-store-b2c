@extends('layouts.app')
@section('title', '[DEV] Cửa hàng thử giỏ hàng')
@section('content')
<div class="alert alert-warning">Trang tạm chỉ dùng khi APP_ENV=local, để thử giỏ hàng khi chưa có trang sản phẩm của Tuyến.</div>
<div class="row g-3">
@foreach($products as $p)
    <div class="col-md-6 col-lg-4">
        <form action="{{ route('cart.add') }}" method="POST" class="card border-0 shadow-sm p-3 h-100">
            @csrf
            <input type="hidden" name="product_id" value="{{ $p->id }}">
            <h6 class="fw-bold">{{ $p->name }}</h6>
            <div class="text-primary fw-bold mb-1">{{ number_format($p->effective_price, 0, ',', '.') }} đ</div>
            <div class="small text-secondary mb-2">Tồn: {{ $p->stock_quantity }}</div>
            @if($p->variants->isNotEmpty())
                <select name="variant_id" class="form-select form-select-sm mb-2" required>
                    @foreach($p->variants as $v)
                        <option value="{{ $v->id }}">{{ $v->variant_name }} (+{{ number_format($v->price_adjustment, 0, ',', '.') }}) - còn {{ $v->stock_quantity }}</option>
                    @endforeach
                </select>
            @endif
            <div class="d-flex gap-2 mt-auto">
                <input type="number" name="quantity" value="1" min="1" step="1" class="form-control form-control-sm" style="max-width:80px">
                <button class="btn btn-sm btn-primary">Thêm vào giỏ</button>
            </div>
        </form>
    </div>
@endforeach
</div>
@endsection
