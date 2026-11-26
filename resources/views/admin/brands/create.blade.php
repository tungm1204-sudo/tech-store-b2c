@extends('layouts.admin')

@section('page_title', 'Thêm thương hiệu')

@section('content')

<form method="POST" action="{{ route('admin.brands.store') }}">
    @csrf
    @include('admin.brands._form')
</form>

<a href="{{ route('admin.brands.index') }}">Quay lại</a>
@endsection