@extends('layouts.app')

@section('content')
<h1>Thêm sản phẩm</h1>

<form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.products._form')
</form>

<a href="{{ route('admin.products.index') }}">Quay lại</a>
@endsection