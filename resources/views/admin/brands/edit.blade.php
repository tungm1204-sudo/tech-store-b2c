@extends('layouts.app')

@section('content')
<h1>Sửa thương hiệu</h1>

<form method="POST" action="{{ route('admin.brands.update', $brand) }}">
    @csrf
    @method('PUT')
    @include('admin.brands._form')
</form>

<a href="{{ route('admin.brands.index') }}">Quay lại</a>
@endsection