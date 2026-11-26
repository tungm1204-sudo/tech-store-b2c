@csrf
<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Mã *</label>
        <input name="code" class="form-control text-uppercase" maxlength="50" required value="{{ old('code', $coupon->code) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Loại giảm *</label>
        <select name="discount_type" class="form-select">
            <option value="percent" @selected(old('discount_type', $coupon->discount_type) === 'percent')>Phần trăm (%)</option>
            <option value="fixed" @selected(old('discount_type', $coupon->discount_type) === 'fixed')>Số tiền cố định (đ)</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Giá trị giảm *</label>
        <input type="number" step="1" min="1" name="discount_value" class="form-control" required value="{{ old('discount_value', $coupon->discount_value ? (int) $coupon->discount_value : '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Đơn tối thiểu (đ)</label>
        <input type="number" step="1000" min="0" name="min_order_amount" class="form-control" value="{{ old('min_order_amount', (int) $coupon->min_order_amount) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Số lượt dùng tối đa *</label>
        <input type="number" min="1" name="max_uses" class="form-control" required value="{{ old('max_uses', $coupon->max_uses) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Hết hạn</label>
        <input type="datetime-local" name="expires_at" class="form-control" value="{{ old('expires_at', optional($coupon->expires_at)->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-12">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $coupon->is_active))>
            <label class="form-check-label" for="is_active">Đang hoạt động</label>
        </div>
    </div>
</div>
<div class="mt-4 d-flex gap-2">
    <button class="btn btn-primary">Lưu</button>
    <a href="{{ route('admin.coupons.index') }}" class="btn btn-outline-secondary">Quay lại</a>
</div>
