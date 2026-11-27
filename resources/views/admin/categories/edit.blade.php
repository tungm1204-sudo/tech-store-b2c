@extends('layouts.admin')

@section('page_title', 'Sửa danh mục')

@section('content')

<form method="POST" action="{{ route('admin.categories.update', $category) }}">
    @csrf
    @method('PUT')
    @include('admin.categories._form')
</form>

<a href="{{ route('admin.categories.index') }}">Quay lại</a>
@endsection