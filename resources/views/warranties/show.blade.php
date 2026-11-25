@extends('layouts.app')
@section('title', 'Yêu cầu ' . $rma->rma_code)

@section('content')
@php
    $steps = ['submitted', 'inspecting', in_array($rma->status, ['rejected']) ? 'rejected' : 'approved', 'completed'];
    $order = ['submitted' => 0, 'inspecting' => 1, 'approved' => 2, 'rejected' => 2, 'completed' => 3];
    $cur = $order[$rma->status] ?? 0;
@endphp
<a href="{{ route('warranties.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Yêu cầu của tôi</a>
<div class="d-flex justify-content-between align-items-center my-2">
    <h3 class="fw-bold mb-0">{{ $rma->rma_code }}</h3>
    <span class="badge fs-6 bg-{{ $rma->status_badge }}">{{ $rma->status_label }}</span>
</div>

<div class="card border-0 shadow-sm mb-3"><div class="card-body"><div class="d-flex justify-content-between text-center">
    @foreach($steps as $i => $s)
        <div class="flex-fill">
            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center {{ $i <= $cur ? ($s === 'rejected' ? 'bg-danger' : 'bg-success') . ' text-white' : 'bg-light text-muted border' }}" style="width:36px;height:36px">{{ $i + 1 }}</div>
            <div class="small mt-1 {{ $i === $cur ? 'fw-bold' : 'text-muted' }}">{{ \App\Models\WarrantyRequest::STATUS_LABELS[$s] }}</div>
        </div>
    @endforeach
</div></div></div>

<div class="card border-0 shadow-sm"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">Sản phẩm</dt><dd class="col-sm-9">{{ $rma->orderItem->product_name ?? '—' }} (Đơn <a href="{{ route('orders.show', $rma->order) }}">{{ $rma->order->order_code }}</a>)</dd>
        <dt class="col-sm-3">Loại yêu cầu</dt><dd class="col-sm-9">{{ $rma->request_type === 'return' ? 'Đổi trả' : 'Bảo hành / Sửa chữa' }}</dd>
        <dt class="col-sm-3">Serial / IMEI</dt><dd class="col-sm-9">{{ $rma->serial_number }}</dd>
        <dt class="col-sm-3">Mô tả lỗi</dt><dd class="col-sm-9">{!! nl2br(e($rma->issue_description)) !!}</dd>
        @if($rma->evidence_url)<dt class="col-sm-3">Minh chứng</dt><dd class="col-sm-9"><a href="{{ $rma->evidence_url }}" target="_blank" rel="noopener">Xem tệp đính kèm</a></dd>@endif
        @if($rma->resolution_note)<dt class="col-sm-3">Phản hồi của cửa hàng</dt><dd class="col-sm-9">{!! nl2br(e($rma->resolution_note)) !!}</dd>@endif
        <dt class="col-sm-3">Ngày gửi</dt><dd class="col-sm-9">{{ $rma->created_at->format('d/m/Y H:i') }}</dd>
    </dl>
</div></div>
@endsection
