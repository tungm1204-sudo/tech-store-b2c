@extends('layouts.app')

@section('content')
<h1>Sửa danh mục</h1>

<form method="POST" action="{{ route('admin.categories.update', $category) }}">
    @csrf
    @method('PUT')
    @include('admin.categories._form')
</form>

<a href="{{ route('admin.categories.index') }}">Quay lại</a>
@endsection