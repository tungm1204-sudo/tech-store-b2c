@extends('layouts.admin')
@section('title', 'Thêm mã giảm giá')
@section('page_title', 'Thêm mã giảm giá')
@section('content')
<form action="{{ route('admin.coupons.store') }}" method="POST" class="card border-0 shadow-sm p-4">
    @include('admin.coupons._form')
</form>
@endsection
