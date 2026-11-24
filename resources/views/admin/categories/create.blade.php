@extends('layouts.admin')

@section('page_title', 'Thêm danh mục')

@section('content')

<form method="POST" action="{{ route('admin.categories.store') }}">
    @csrf
    @include('admin.categories._form')
</form>

<a href="{{ route('admin.categories.index') }}">Quay lại</a>
@endsection