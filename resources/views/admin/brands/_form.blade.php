@if ($errors->any())
    <ul style="color:red">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
@endif

<div>
    <label>Tên thương hiệu</label>
    <input type="text" name="name" value="{{ old('name', $brand->name ?? '') }}" required>
</div>

<div>
    <label>Quốc gia</label>
    <input type="text" name="country" value="{{ old('country', $brand->country ?? '') }}">
</div>

<button type="submit">Lưu</button>