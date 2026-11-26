@extends('layouts.admin')
@section('title', 'RMA ' . $rma->rma_code)
@section('page_title', 'Xử lý yêu cầu ' . $rma->rma_code)

@section('content')
<a href="{{ route('admin.warranties.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Danh sách RMA</a>
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="card kpi-card"><div class="card-body">
            <div class="d-flex justify-content-between mb-2">
                <h5 class="fw-bold mb-0">{{ $rma->orderItem->product_name ?? '—' }}</h5>
                <span class="badge fs-6 bg-{{ $rma->status_badge }}">{{ $rma->status_label }}</span>
            </div>
            <dl class="row mb-0 small">
                <dt class="col-sm-3">Khách hàng</dt><dd class="col-sm-9">{{ $rma->user->name ?? '—' }} ({{ $rma->user->email ?? '' }})</dd>
                <dt class="col-sm-3">Đơn hàng</dt><dd class="col-sm-9"><a href="{{ route('admin.orders.show', $rma->order) }}">{{ $rma->order->order_code }}</a>
                    @if($rma->order->delivered_at) — giao ngày {{ $rma->order->delivered_at->format('d/m/Y') }} @endif</dd>
                <dt class="col-sm-3">Loại yêu cầu</dt><dd class="col-sm-9">{{ $rma->request_type === 'return' ? 'Đổi trả' : 'Bảo hành / Sửa chữa' }}</dd>
                <dt class="col-sm-3">Serial / IMEI</dt><dd class="col-sm-9">{{ $rma->serial_number }}</dd>
                <dt class="col-sm-3">Mô tả lỗi</dt><dd class="col-sm-9">{!! nl2br(e($rma->issue_description)) !!}</dd>
                @if($rma->evidence_url)<dt class="col-sm-3">Minh chứng</dt><dd class="col-sm-9"><a href="{{ $rma->evidence_url }}" target="_blank" rel="noopener">Xem tệp đính kèm</a></dd>@endif
                @if($rma->resolution_note)<dt class="col-sm-3">Ghi chú xử lý</dt><dd class="col-sm-9">{!! nl2br(e($rma->resolution_note)) !!}</dd>@endif
                @if($rma->handler)<dt class="col-sm-3">Người xử lý</dt><dd class="col-sm-9">{{ $rma->handler->name }}</dd>@endif
                <dt class="col-sm-3">Ngày gửi</dt><dd class="col-sm-9">{{ $rma->created_at->format('d/m/Y H:i') }}</dd>
            </dl>
        </div></div>
    </div>
    <div class="col-lg-4">
        @if(count($next))
            <div class="card kpi-card"><div class="card-header bg-white fw-bold">Thao tác</div><div class="card-body">
                <form method="POST" action="{{ route('admin.warranties.transition', $rma) }}">
                    @csrf
                    <label class="form-label small fw-semibold">Ghi chú / phương án xử lý <span class="text-muted">(bắt buộc khi duyệt hoặc từ chối)</span></label>
                    <textarea name="note" rows="3" class="form-control mb-2" maxlength="2000">{{ old('note') }}</textarea>
                    <div class="d-grid gap-2">
                        @foreach($next as $to)
                            <button name="to" value="{{ $to }}" class="btn {{ $to === 'rejected' ? 'btn-outline-danger' : 'btn-primary' }}">{{ $actions[$to] }}</button>
                        @endforeach
                    </div>
                </form>
            </div></div>
        @elseif(! $isAdmin && $rma->status === 'inspecting')
            <div class="alert alert-info small">Đang chờ Quản lý (Admin) phê duyệt hoặc từ chối.</div>
        @endif
    </div>
</div>
@endsection
