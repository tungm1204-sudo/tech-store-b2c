@extends('layouts.app')

@section('content')
<h1>Thêm danh mục</h1>

<form method="POST" action="{{ route('admin.categories.store') }}">
    @csrf
    @include('admin.categories._form')
</form>

<a href="{{ route('admin.categories.index') }}">Quay lại</a>
@endsection