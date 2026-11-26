@extends('layouts.admin')
@section('title', 'Phiếu Nhập kho')
@section('page_title', 'Phiếu Nhập kho')

@section('content')
@if($lowStock->isNotEmpty())
    <div class="alert alert-warning">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-1"></i>{{ $lowStock->count() }} sản phẩm sắp hết hàng</div>
        <div class="small">
            @foreach($lowStock->take(8) as $p)
                <span class="badge bg-white text-dark border me-1">{{ $p->name }}: còn {{ $p->stock_quantity }}</span>
            @endforeach
            @if($lowStock->count() > 8) <span class="text-muted">và {{ $lowStock->count() - 8 }} sản phẩm khác...</span>@endif
        </div>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <form method="GET"><select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Tất cả trạng thái</option>
        <option value="pending" @selected(request('status') === 'pending')>Chờ duyệt</option>
        <option value="approved" @selected(request('status') === 'approved')>Đã duyệt</option>
        <option value="cancelled" @selected(request('status') === 'cancelled')>Đã hủy</option>
    </select></form>
    <a href="{{ route('admin.stock_receipts.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Lập phiếu nhập</a>
</div>

@php $badge = ['pending' => 'warning', 'approved' => 'success', 'cancelled' => 'secondary']; $label = ['pending' => 'Chờ duyệt', 'approved' => 'Đã duyệt', 'cancelled' => 'Đã hủy']; @endphp
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
    <thead class="table-light"><tr><th>Mã phiếu</th><th>Nhà cung cấp</th><th class="text-center">Tổng SL</th><th class="text-end">Tổng giá trị</th><th>Trạng thái</th><th>Người lập</th><th>Ngày lập</th><th></th></tr></thead>
    <tbody>
    @forelse($receipts as $r)
        <tr>
            <td class="fw-bold">{{ $r->receipt_code }}</td>
            <td>{{ $r->supplier_name }}</td>
            <td class="text-center">{{ $r->total_qty ?? 0 }}</td>
            <td class="text-end">{{ number_format($r->total_cost, 0, ',', '.') }}₫</td>
            <td><span class="badge bg-{{ $badge[$r->status] ?? 'secondary' }}">{{ $label[$r->status] ?? $r->status }}</span></td>
            <td>{{ $r->creator->name ?? '—' }}</td>
            <td>{{ $r->created_at->format('d/m/Y') }}</td>
            <td><a href="{{ route('admin.stock_receipts.show', $r) }}" class="btn btn-sm btn-outline-primary">Chi tiết</a></td>
        </tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">Chưa có phiếu nhập nào.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
<div class="mt-3">{{ $receipts->links('pagination::bootstrap-5') }}</div>
@endsection
