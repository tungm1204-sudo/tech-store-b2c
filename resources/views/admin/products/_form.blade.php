@php
    $p = $product ?? null;
    $val = fn ($field, $default = '') => old($field, $p?->{$field} ?? $default);
@endphp

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-primary mb-3">Thông tin chung</h6>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Tên sản phẩm</label>
                        <input type="text" name="name" class="form-control" value="{{ $val('name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SKU</label>
                        <input type="text" name="sku" class="form-control" value="{{ $val('sku') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Thương hiệu</label>
                        <select name="brand_id" class="form-select" required>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $p?->brand_id) == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Danh mục</label>
                        <select name="category_id" class="form-select" required>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $p?->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nhu cầu sử dụng</label>
                        <select name="usage_type" class="form-select">
                            <option value="">-- Chọn --</option>
                            @foreach ($usageOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('usage_type', $p?->usage_type) == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mô tả ngắn</label>
                        <input type="text" name="short_description" class="form-control" value="{{ $val('short_description') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Mô tả chi tiết</label>
                        <textarea name="description" rows="4" class="form-control">{{ $val('description') }}</textarea>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold text-primary mb-3">Thông số kỹ thuật</h6>
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">CPU</label><input type="text" name="cpu" class="form-control" value="{{ $val('cpu') }}"></div>
                    <div class="col-md-6"><label class="form-label">GPU</label><input type="text" name="gpu" class="form-control" value="{{ $val('gpu') }}"></div>
                    <div class="col-md-4"><label class="form-label">RAM</label><input type="text" name="ram" class="form-control" value="{{ $val('ram') }}" placeholder="VD: 16GB"></div>
                    <div class="col-md-4"><label class="form-label">ROM</label><input type="text" name="rom" class="form-control" value="{{ $val('rom') }}" placeholder="VD: 512GB SSD"></div>
                    <div class="col-md-4"><label class="form-label">Pin</label><input type="text" name="battery" class="form-control" value="{{ $val('battery') }}"></div>
                    <div class="col-12"><label class="form-label">Màn hình</label><input type="text" name="screen" class="form-control" value="{{ $val('screen') }}"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-primary mb-3">Giá và kho</h6>
                <div class="mb-3">
                    <label class="form-label">Giá gốc (đ)</label>
                    <input type="number" name="price" class="form-control" value="{{ $val('price') }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Giá khuyến mãi (đ)</label>
                    <input type="number" name="sale_price" class="form-control" value="{{ $val('sale_price') }}">
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Tồn kho</label>
                        <input type="number" name="stock_quantity" class="form-control" value="{{ $val('stock_quantity', 0) }}" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Ngưỡng cảnh báo</label>
                        <input type="number" name="low_stock_threshold" class="form-control" value="{{ $val('low_stock_threshold', 5) }}" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-primary mb-3">Ảnh đại diện</h6>
                @if (!empty($p?->thumbnail))
                    <img src="{{ asset($p->thumbnail) }}" class="img-fluid rounded-3 bg-light p-2 mb-3" alt="">
                @endif
                <input type="file" name="thumbnail_file" class="form-control" accept="image/*">
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold text-primary mb-3">Trạng thái</h6>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active"
                           @checked(old('is_active', $p?->is_active ?? true))>
                    <label class="form-check-label" for="is_active">Đang bán</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_featured" value="1" id="is_featured"
                           @checked(old('is_featured', $p?->is_featured ?? false))>
                    <label class="form-check-label" for="is_featured">Sản phẩm nổi bật</label>
                </div>
            </div>
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check2-circle me-1"></i>Lưu sản phẩm</button>
            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Hủy</a>
        </div>
    </div>
</div>