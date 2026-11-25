@extends('layouts.admin')
@section('title', 'Sửa mã giảm giá')
@section('page_title', 'Sửa mã ' . $coupon->code)
@section('content')
<form action="{{ route('admin.coupons.update', $coupon) }}" method="POST" class="card border-0 shadow-sm p-4">
    @method('PUT')
    @include('admin.coupons._form')
</form>
@endsection
