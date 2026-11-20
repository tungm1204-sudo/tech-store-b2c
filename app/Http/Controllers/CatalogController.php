<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\Category;

class CatalogController extends Controller
{
    // Biểu thức giá hiệu lực, khớp với accessor effective_price trong Product
    private const EFFECTIVE_PRICE_SQL = 'CASE WHEN sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price
                                          THEN sale_price ELSE price END';
    private const USAGE_OPTIONS = ['do-hoa' => 'Đồ họa', 'gaming' => 'Gaming', 'van-phong' => 'Văn phòng'];

    private const RAM_OPTIONS = ['6GB', '8GB', '12GB', '16GB', '18GB', '32GB'];

    private const STORAGE_OPTIONS = ['128GB', '256GB', '512GB', '1TB'];

// Khóa là tên hiển thị, giá trị là các từ khóa có thể nằm trong variant_name
    private const COLOR_OPTIONS = [
        'Đen'   => ['Đen', 'Black'],
        'Trắng' => ['Trắng', 'White'],
        'Bạc'   => ['Bạc', 'Silver'],
        'Xám'   => ['Xám', 'Graphite', 'Gray'],
        'Xanh'  => ['Xanh', 'Blue'],
        'Hồng'  => ['Hồng', 'Pink'],
    ];

    public function index(Request $request)
    {
        $query = Product::with(['brand', 'category'])
            ->where('is_active', true);

        $query->when($request->filled('q'), function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->q . '%');
        });

        // Lọc theo giá hiệu lực để khớp với giá hiển thị
        $query->when($request->filled('min'), function ($q) use ($request) {
            $q->whereRaw(self::EFFECTIVE_PRICE_SQL . ' >= ?', [(float) $request->min]);
        });
        $query->when($request->filled('max'), function ($q) use ($request) {
            $q->whereRaw(self::EFFECTIVE_PRICE_SQL . ' <= ?', [(float) $request->max]);
        });

        $query->when($request->filled('brand'), function ($q) use ($request) {
            $q->whereIn('brand_id', array_map('intval', (array) $request->brand));
        });
        $query->when($request->filled('category'), function ($q) use ($request) {
            $q->where('category_id', (int) $request->category);
        });

        $query->when(
            is_string($request->usage) && array_key_exists($request->usage, self::USAGE_OPTIONS),
            function ($q) use ($request) {
                $q->where('usage_type', $request->usage);
            }
        );
        // RAM: chỉ nhận giá trị trong danh sách cho phép
        $query->when(
            is_string($request->ram) && in_array($request->ram, self::RAM_OPTIONS, true),
            function ($q) use ($request) {
                $q->where('ram', $request->ram);
            }
        );

// Dung lượng: khớp đầu chuỗi rom, ví dụ "512GB" khớp "512GB SSD"
        $query->when(
            is_string($request->storage) && in_array($request->storage, self::STORAGE_OPTIONS, true),
            function ($q) use ($request) {
                $q->where('rom', 'like', $request->storage . '%');
            }
        );

// SSD: rom có chứa SSD hoặc NVMe
        $query->when($request->boolean('ssd'), function ($q) {
            $q->where(function ($s) {
                $s->where('rom', 'like', '%SSD%')->orWhere('rom', 'like', '%NVMe%');
            });
        });

// Màu: tìm từ khóa trong tên biến thể
        $query->when(
            is_string($request->color) && isset(self::COLOR_OPTIONS[$request->color]),
            function ($q) use ($request) {
                $keywords = self::COLOR_OPTIONS[$request->color];

                $q->whereHas('variants', function ($v) use ($keywords) {
                    $v->where(function ($w) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            $w->orWhere('variant_name', 'like', '%' . $keyword . '%');
                        }
                    });
                });
            }
        );
        $products = $query->orderBy('price')
            ->paginate(12)
            ->withQueryString();

        $brands = Brand::orderBy('name')->get();

        $categories = Category::orderBy('name')->get();

        return view('catalog.index', [
            'products'       => $products,
            'brands'         => $brands,
            'categories'     => $categories,
            'usageOptions'   => self::USAGE_OPTIONS,
            'ramOptions'     => self::RAM_OPTIONS,
            'storageOptions' => self::STORAGE_OPTIONS,
            'colorOptions'   => array_keys(self::COLOR_OPTIONS),
        ]);

    }

    public function show(string $slug)
    {
        $product = Product::with(['brand', 'category', 'images', 'variants'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('catalog.show', compact('product'));
    }
}