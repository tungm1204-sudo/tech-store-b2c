<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    private const USAGE_OPTIONS = [
        'do-hoa'    => 'Đồ họa',
        'gaming'    => 'Gaming',
        'van-phong' => 'Văn phòng',
    ];

    // Danh sách sản phẩm, có tìm kiếm theo tên
    public function index(Request $request)
    {
        $products = Product::with(['brand', 'category'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%');
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    // Form thêm mới
    public function create()
    {
        return view('admin.products.create', [
            'brands'       => Brand::orderBy('name')->get(),
            'categories'   => Category::orderBy('name')->get(),
            'usageOptions' => self::USAGE_OPTIONS,
        ]);
    }

    // Lưu sản phẩm mới
    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data = $this->handleThumbnail($request, $data);

        $data['slug'] = $this->uniqueSlug($data['name']);

        Product::create($data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã thêm sản phẩm.');
    }

    // Form sửa
    public function edit(Product $product)
    {
        return view('admin.products.edit', [
            'product'      => $product,
            'brands'       => Brand::orderBy('name')->get(),
            'categories'   => Category::orderBy('name')->get(),
            'usageOptions' => self::USAGE_OPTIONS,
        ]);
    }

    // Cập nhật sản phẩm. Không đổi slug để link cũ không bị hỏng.
    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);
        $data = $this->handleThumbnail($request, $data);

        $product->update($data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã cập nhật sản phẩm.');
    }

    // Xóa mềm: chỉ ẩn khỏi trang khách, không xóa dữ liệu
    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);

        return redirect()->route('admin.products.index')
            ->with('success', 'Đã ẩn sản phẩm khỏi trang khách.');
    }

    // Quy tắc kiểm tra dữ liệu, dùng chung cho store và update
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:200'],
            'sku'         => ['required', 'string', 'max:50',
                              Rule::unique('products', 'sku')->ignore($product?->id)],
            'brand_id'    => ['required', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'price'       => ['required', 'numeric', 'min:1'],
            'sale_price'  => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'stock_quantity'      => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'usage_type'  => ['nullable', Rule::in(array_keys(self::USAGE_OPTIONS))],
            'cpu'     => ['nullable', 'string', 'max:100'],
            'ram'     => ['nullable', 'string', 'max:50'],
            'rom'     => ['nullable', 'string', 'max:50'],
            'gpu'     => ['nullable', 'string', 'max:100'],
            'screen'  => ['nullable', 'string', 'max:100'],
            'battery' => ['nullable', 'string', 'max:100'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'description'       => ['nullable', 'string'],
            'thumbnail_file'    => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        // Checkbox không tick thì không được gửi lên, nên phải đọc bằng boolean()
        $data['is_active']   = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }

    // Nếu có chọn ảnh mới thì lưu file và ghi đường dẫn vào cột thumbnail
    private function handleThumbnail(Request $request, array $data): array
    {
        if ($request->hasFile('thumbnail_file')) {
            $file = $request->file('thumbnail_file');
            $filename = Str::slug($data['name']) . '-' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('images/products'), $filename);
            $data['thumbnail'] = '/images/products/' . $filename;
        }

        unset($data['thumbnail_file']);

        return $data;
    }

    // Tạo slug, nếu trùng thì thêm số phía sau: ten-san-pham, ten-san-pham-2, ...
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}