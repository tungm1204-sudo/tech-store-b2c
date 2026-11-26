<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Trang tạm để Phúc test giỏ hàng khi nhánh của Tuyến (trang sản phẩm) chưa được ghép.
 * Route /dev/shop chỉ đăng ký khi APP_ENV=local. Có thể xóa khi đã ghép nhánh catalog.
 */
class DevShopController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('variants')->where('is_active', true)->orderBy('name')->get();

        // Vẫn giữ JSON cho script kiểm thử/E2E; trình duyệt thì dùng Blade.
        if ($request->expectsJson() || $request->boolean('json')) {
            return response()->json(compact('products'));
        }

        return view('dev.shop', compact('products'));
    }
}
