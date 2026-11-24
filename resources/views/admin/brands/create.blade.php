@extends('layouts.app')

@section('content')
<h1>Thêm thương hiệu</h1>

<form method="POST" action="{{ route('admin.brands.store') }}">
    @csrf
    @include('admin.brands._form')
</form>

<a href="{{ route('admin.brands.index') }}">Quay lại</a>
@endsection