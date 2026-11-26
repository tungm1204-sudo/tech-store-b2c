@extends('layouts.admin')
@section('title', 'Phiếu ' . $receipt->receipt_code)
@section('page_title', 'Phiếu nhập ' . $receipt->receipt_code)

@section('content')
@php $badge = ['pending' => 'warning', 'approved' => 'success', 'cancelled' => 'secondary']; $label = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'cancelled' => 'Đã hủy']; @endphp
<a href="{{ route('admin.stock_receipts.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Danh sách phiếu</a>
<div class="row g-3 mt-1">
    <div class="col-lg-8">
        <div class="card kpi-card"><div class="table-responsive"><table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>Sản phẩm</th><th class="text-center">SL</th><th class="text-end">Giá nhập</th><th class="text-end">Thành tiền</th></tr></thead>
            <tbody>
            @foreach($receipt->items as $it)
                <tr><td>{{ $it->product->name ?? '—' }}</td><td class="text-center">{{ $it->quantity }}</td>
                    <td class="text-end">{{ number_format($it->unit_cost, 0, ',', '.') }}₫</td>
                    <td class="text-end">{{ number_format($it->unit_cost * $it->quantity, 0, ',', '.') }}₫</td></tr>
            @endforeach
            </tbody>
            <tfoot class="table-light"><tr><td colspan="3" class="text-end fw-bold">Tổng giá trị</td><td class="text-end fw-bold">{{ number_format($receipt->total_cost, 0, ',', '.') }}₫</td></tr></tfoot>
        </table></div></div>
        @if($receipt->imei_batch_note)
            <div class="card kpi-card mt-3"><div class="card-body small"><div class="fw-bold mb-1">Ghi chú / Serial-IMEI</div><pre class="mb-0" style="white-space:pre-wrap">{{ $receipt->imei_batch_note }}</pre></div></div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="card kpi-card mb-3"><div class="card-body small">
            <div class="mb-1">Trạng thái: <span class="badge bg-{{ $badge[$receipt->status] ?? 'secondary' }}">{{ $label[$receipt->status] ?? $receipt->status }}</span></div>
            <div>Nhà cung cấp: <strong>{{ $receipt->supplier_name }}</strong></div>
            <div>Người lập: {{ $receipt->creator->name ?? '—' }} ({{ $receipt->created_at->format('d/m/Y H:i') }})</div>
            @if($receipt->approver)<div>Người duyệt: {{ $receipt->approver->name }} ({{ $receipt->approved_at?->format('d/m/Y H:i') }})</div>@endif
        </div></div>
        @if($receipt->status === 'pending')
            <div class="d-grid gap-2">
                @if(auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('admin.stock_receipts.approve', $receipt) }}" onsubmit="return confirm('Duyệt phiếu và cộng tồn kho?')">@csrf
                        <button class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i>Duyệt & cộng kho</button></form>
                @else
                    <div class="alert alert-info small mb-0">Chờ Quản trị viên duyệt để cộng tồn kho.</div>
                @endif
                <form method="POST" action="{{ route('admin.stock_receipts.cancel', $receipt) }}" onsubmit="return confirm('Hủy phiếu này?')">@csrf
                    <button class="btn btn-outline-danger w-100">Hủy phiếu</button></form>
            </div>
        @endif
    </div>
</div>
@endsection
