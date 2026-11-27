@extends('layouts.admin')

@section('page_title', 'Thêm sản phẩm')

@section('content')
<form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.products._form')
</form>

<a href="{{ route('admin.products.index') }}">Quay lại</a>
@endsection