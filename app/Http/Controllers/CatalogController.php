<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    // Biểu thức giá hiệu lực, khớp với accessor effective_price trong Product
    private const EFFECTIVE_PRICE_SQL = 'CASE WHEN sale_price IS NOT NULL AND sale_price > 0 AND sale_price < price THEN sale_price ELSE price END';

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
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->where('is_active', true);

        $query->when($request->filled('q'), function ($q) use ($request) {
            $kw = trim((string) $request->q);
            $q->where(function ($sub) use ($kw) {
                $sub->where('name', 'like', '%' . $kw . '%')
                    ->orWhere('sku', 'like', '%' . $kw . '%')
                    ->orWhere('cpu', 'like', '%' . $kw . '%');
            });
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

        // Hỗ trợ lọc danh mục bằng cả ID (từ form select) và slug (từ menu header ?category=laptop)
        $selectedCategoryId = null;
        if ($request->filled('category')) {
            $catParam = trim((string) $request->category);
            if (ctype_digit($catParam)) {
                $selectedCategoryId = (int) $catParam;
                $query->where('category_id', $selectedCategoryId);
            } else {
                $matchedCategory = Category::where('slug', $catParam)->first();
                $selectedCategoryId = $matchedCategory?->id;
                $query->where('category_id', $selectedCategoryId ?? 0);
            }
        }

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

        // Sắp xếp theo giá hiệu lực / bán chạy / mới nhất
        $sort = (string) $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderByRaw(self::EFFECTIVE_PRICE_SQL . ' ASC');
                break;
            case 'price_desc':
                $query->orderByRaw(self::EFFECTIVE_PRICE_SQL . ' DESC');
                break;
            case 'bestseller':
                $query->orderByDesc('sold_count');
                break;
            default:
                $query->orderByDesc('is_featured')->orderByDesc('id');
                break;
        }

        $products = $query->paginate(12)->withQueryString();
        $brands = Brand::orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('catalog.index', [
            'products'           => $products,
            'brands'             => $brands,
            'categories'         => $categories,
            'selectedCategoryId' => $selectedCategoryId,
            'usageOptions'       => self::USAGE_OPTIONS,
            'ramOptions'         => self::RAM_OPTIONS,
            'storageOptions'     => self::STORAGE_OPTIONS,
            'colorOptions'       => array_keys(self::COLOR_OPTIONS),
            'sort'               => $sort,
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::with(['brand', 'category', 'images', 'variants'])
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return view('catalog.show', compact('product'));
    }
}
