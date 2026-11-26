@extends('layouts.admin')
@section('title', 'Lập phiếu nhập kho')
@section('page_title', 'Lập phiếu nhập kho')

@section('content')
@php $oldItems = old('items', [['product_id' => '', 'quantity' => 1, 'unit_cost' => 0]]); @endphp
<a href="{{ route('admin.stock_receipts.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Danh sách phiếu</a>
<form method="POST" action="{{ route('admin.stock_receipts.store') }}" class="card kpi-card mt-2"><div class="card-body">
    @csrf
    <div class="row g-3 mb-3">
        <div class="col-md-6"><label class="form-label fw-semibold">Nhà cung cấp *</label>
            <input type="text" name="supplier_name" class="form-control" value="{{ old('supplier_name') }}" maxlength="150" required></div>
        <div class="col-md-6"><label class="form-label fw-semibold">Ghi chú</label>
            <input type="text" name="note" class="form-control" value="{{ old('note') }}" maxlength="1000"></div>
    </div>

    <label class="form-label fw-semibold">Sản phẩm nhập *</label>
    <div id="lines">
        @foreach($oldItems as $i => $row)
            <div class="row g-2 mb-2 line">
                <div class="col-md-6"><select name="items[{{ $i }}][product_id]" class="form-select" required>
                    <option value="">-- Chọn sản phẩm --</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected((string) ($row['product_id'] ?? '') === (string) $p->id)>{{ $p->name }} ({{ $p->sku }}) — tồn {{ $p->stock_quantity }}</option>
                    @endforeach
                </select></div>
                <div class="col-md-2"><input type="number" name="items[{{ $i }}][quantity]" class="form-control" min="1" value="{{ $row['quantity'] ?? 1 }}" required></div>
                <div class="col-md-3"><input type="number" name="items[{{ $i }}][unit_cost]" class="form-control" min="0" step="1000" value="{{ $row['unit_cost'] ?? 0 }}" placeholder="Giá nhập (₫)" required></div>
                <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100 remove-line"><i class="bi bi-trash"></i></button></div>
            </div>
        @endforeach
    </div>
    <button type="button" id="add-line" class="btn btn-sm btn-outline-primary mb-3"><i class="bi bi-plus"></i> Thêm dòng</button>

    <div class="mb-3">
        <label class="form-label fw-semibold">Kiểm kê Serial / IMEI <span class="text-muted fw-normal">(mỗi mã một dòng; nếu nhập thì phải khớp tổng số lượng)</span></label>
        <textarea name="serials" rows="4" class="form-control font-monospace">{{ old('serials') }}</textarea>
    </div>
    <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu phiếu (chờ duyệt)</button>
</div></form>
@endsection

@push('scripts')
<script>
(function () {
    const wrap = document.getElementById('lines');
    function reindex() {
        wrap.querySelectorAll('.line').forEach((row, i) => {
            row.querySelectorAll('[name]').forEach(el => {
                el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
            });
        });
    }
    document.getElementById('add-line').addEventListener('click', () => {
        const clone = wrap.querySelector('.line').cloneNode(true);
        clone.querySelectorAll('select').forEach(s => s.selectedIndex = 0);
        clone.querySelectorAll('input[name$="[quantity]"]').forEach(i => i.value = 1);
        clone.querySelectorAll('input[name$="[unit_cost]"]').forEach(i => i.value = 0);
        wrap.appendChild(clone);
        reindex();
    });
    wrap.addEventListener('click', e => {
        const btn = e.target.closest('.remove-line');
        if (btn && wrap.querySelectorAll('.line').length > 1) { btn.closest('.line').remove(); reindex(); }
    });
})();
</script>
@endpush
