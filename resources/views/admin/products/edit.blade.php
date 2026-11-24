@extends('layouts.app')

@section('content')
<h1>Sửa sản phẩm</h1>

<form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.products._form')
</form>

<a href="{{ route('admin.products.index') }}">Quay lại</a>
@endsection