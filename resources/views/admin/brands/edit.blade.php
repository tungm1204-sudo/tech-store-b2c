@extends('layouts.admin')

@section('page_title', 'Sửa thương hiệu')

@section('content')



<form method="POST" action="{{ route('admin.brands.update', $brand) }}">
    @csrf
    @method('PUT')
    @include('admin.brands._form')
</form>

<a href="{{ route('admin.brands.index') }}">Quay lại</a>
@endsection