<div class="card border-0 shadow-sm rounded-4" style="max-width:640px">
    <div class="card-body p-4">
        <div class="mb-3">
            <label class="form-label fw-semibold">Tên danh mục *</label>
            <input type="text" name="name" class="form-control" maxlength="100" value="{{ old('name', $category->name ?? '') }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label fw-semibold">Icon Bootstrap <span class="text-muted fw-normal">(VD: bi-laptop, bi-phone, bi-headphones)</span></label>
            <input type="text" name="icon" class="form-control" maxlength="50" value="{{ old('icon', $category->icon ?? 'bi-box') }}" placeholder="bi-laptop">
        </div>
        <div class="mb-4">
            <label class="form-label fw-semibold">Mô tả</label>
            <textarea name="description" rows="3" class="form-control" maxlength="500">{{ old('description', $category->description ?? '') }}</textarea>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Lưu danh mục</button>
            <a href="{{ route('admin.categories.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </div>
</div>
