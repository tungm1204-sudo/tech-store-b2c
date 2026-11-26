<div class="card border-0 shadow-sm rounded-4" style="max-width:640px">
    <div class="card-body p-4">
        <div class="mb-3">
            <label class="form-label">Tên thương hiệu</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $brand->name ?? '') }}" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Quốc gia</label>
            <input type="text" name="country" class="form-control" value="{{ old('country', $brand->country ?? '') }}">
        </div>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>Lưu</button>
    </div>
</div>