@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<div>
    <label>Tên danh mục</label>
    <input type="text" name="name" value="{{ old('name', $category->name ?? '') }}" required>
</div>

<div>
    <label>Icon (ví dụ bi-laptop)</label>
    <input type="text" name="icon" value="{{ old('icon', $category->icon ?? '') }}">
</div>

<div>
    <label>Mô tả</label>
    <textarea name="description">{{ old('description', $category->description ?? '') }}</textarea>
</div>

<button type="submit">Lưu</button>