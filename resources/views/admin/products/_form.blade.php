@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<h3>Thông tin chung</h3>
<p>Tên: <input type="text" name="name" value="{{ old('name', $product->name ?? '') }}" required></p>
<p>SKU: <input type="text" name="sku" value="{{ old('sku', $product->sku ?? '') }}" required></p>

<p>Thương hiệu:
    <select name="brand_id" required>
        @foreach ($brands as $brand)
            <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id ?? '') == $brand->id)>
                {{ $brand->name }}
            </option>
        @endforeach
    </select>
</p>

<p>Danh mục:
    <select name="category_id" required>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>
                {{ $category->name }}
            </option>
        @endforeach
    </select>
</p>

<p>Nhu cầu sử dụng:
    <select name="usage_type">
        <option value="">-- Chọn --</option>
        @foreach ($usageOptions as $value => $label)
            <option value="{{ $value }}" @selected(old('usage_type', $product->usage_type ?? '') == $value)>
                {{ $label }}
            </option>
        @endforeach
    </select>
</p>

<h3>Giá và kho</h3>
<p>Giá gốc: <input type="number" name="price" value="{{ old('price', $product->price ?? '') }}" required></p>
<p>Giá khuyến mãi: <input type="number" name="sale_price" value="{{ old('sale_price', $product->sale_price ?? '') }}"></p>
<p>Tồn kho: <input type="number" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}" required></p>
<p>Ngưỡng cảnh báo sắp hết: <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 5) }}" required></p>

<h3>Thông số kỹ thuật</h3>
<p>CPU: <input type="text" name="cpu" value="{{ old('cpu', $product->cpu ?? '') }}"></p>
<p>RAM: <input type="text" name="ram" value="{{ old('ram', $product->ram ?? '') }}"></p>
<p>ROM: <input type="text" name="rom" value="{{ old('rom', $product->rom ?? '') }}"></p>
<p>GPU: <input type="text" name="gpu" value="{{ old('gpu', $product->gpu ?? '') }}"></p>
<p>Màn hình: <input type="text" name="screen" value="{{ old('screen', $product->screen ?? '') }}"></p>
<p>Pin: <input type="text" name="battery" value="{{ old('battery', $product->battery ?? '') }}"></p>

<h3>Mô tả</h3>
<p>Mô tả ngắn: <input type="text" name="short_description" value="{{ old('short_description', $product->short_description ?? '') }}"></p>
<p><textarea name="description" rows="5" cols="60">{{ old('description', $product->description ?? '') }}</textarea></p>

<h3>Ảnh đại diện</h3>
@if (!empty($product->thumbnail))
    <p>Ảnh hiện tại: <img src="{{ asset($product->thumbnail) }}" width="100"></p>
@endif
<p><input type="file" name="thumbnail_file" accept="image/*"></p>

<h3>Trạng thái</h3>
<p><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Đang bán</label></p>
<p><label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured ?? false))> Nổi bật</label></p>

<button type="submit">Lưu sản phẩm</button>