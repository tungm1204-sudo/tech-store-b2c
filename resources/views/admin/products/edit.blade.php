@extends('layouts.admin')

@section('page_title', 'Sửa sản phẩm')

@section('content')

<form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.products._form')
</form>

<a href="{{ route('admin.products.index') }}">Quay lại</a>
@endsection