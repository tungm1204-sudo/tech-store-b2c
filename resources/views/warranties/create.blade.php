@extends('layouts.app')
@section('title', 'Gửi yêu cầu Đổi trả / Bảo hành')

@section('content')
<a href="{{ route('warranties.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Yêu cầu của tôi</a>
<h3 class="fw-bold my-2">Gửi yêu cầu Đổi trả / Bảo hành</h3>

@if($items->isEmpty())
    <div class="alert alert-info">Hiện bạn chưa có sản phẩm nào đủ điều kiện (cần thuộc đơn hàng đã giao thành công và còn thiết bị chưa gửi yêu cầu).</div>
@else
<form method="POST" action="{{ route('warranties.store') }}" enctype="multipart/form-data" class="card border-0 shadow-sm"><div class="card-body">
    @csrf
    <div class="mb-3">
        <label class="form-label fw-semibold">Sản phẩm *</label>
        <select name="order_item_id" class="form-select" required>
            <option value="">-- Chọn sản phẩm đã mua --</option>
            @foreach($items as $item)
                <option value="{{ $item->id }}" @selected((int) old('order_item_id', $selected) === $item->id)>
                    {{ $item->product_name }} — Đơn {{ $item->order->order_code }} (còn {{ $service->remainingUnits($item) }} thiết bị)
                </option>
            @endforeach
        </select>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Loại yêu cầu *</label>
            <select name="request_type" class="form-select" required>
                <option value="warranty" @selected(old('request_type', 'warranty') === 'warranty')>Bảo hành / Sửa chữa</option>
                <option value="return" @selected(old('request_type') === 'return')>Đổi trả (trong {{ config('techstore.return_window_days') }} ngày)</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold">Mã Serial / IMEI *</label>
            <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number') }}" maxlength="100" required>
        </div>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Mô tả lỗi *</label>
        <textarea name="issue_description" rows="4" class="form-control" minlength="10" maxlength="2000" required>{{ old('issue_description') }}</textarea>
    </div>
    <div class="mb-3">
        <label class="form-label fw-semibold">Ảnh / Video minh chứng</label>
        <input type="file" name="evidence" class="form-control" accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.webm">
        <div class="form-text">Tối đa {{ round(config('techstore.rma_evidence_max_kb') / 1024) }} MB.</div>
    </div>
    <button class="btn btn-primary"><i class="bi bi-send me-1"></i>Gửi yêu cầu</button>
</div></form>
@endif
@endsection
