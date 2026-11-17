<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trang chủ KHUNG (Ngày 1-2 - Nguyễn Minh Tự dựng sẵn).
 * Mục đích: kiểm tra CSDL 16 bảng + dữ liệu mẫu đã chạy đúng trên máy từng thành viên.
 * Giao diện trang chủ bán hàng hoàn chỉnh sẽ do Tuyến thay thế trong nhánh của mình.
 */
class HomeController extends Controller
{
    /** 16 bảng nghiệp vụ của hệ thống (chuẩn hóa 3NF) */
    private const TABLES = [
        'users', 'user_addresses', 'categories', 'brands', 'products', 'product_images',
        'product_variants', 'coupons', 'carts', 'cart_items', 'orders', 'order_items',
        'warranty_requests', 'stock_receipts', 'stock_receipt_items', 'reviews',
    ];

    public function index()
    {
        $tableStats = [];
        foreach (self::TABLES as $table) {
            $tableStats[$table] = Schema::hasTable($table) ? DB::table($table)->count() : null;
        }

        $products = Schema::hasTable('products')
            ? DB::table('products')->orderBy('id')->take(8)->get(['name', 'price', 'sale_price', 'stock_quantity'])
            : collect();

        return view('home', compact('tableStats', 'products'));
    }

    public function switchLocale(string $locale)
    {
        if (in_array($locale, ['vi', 'en'], true)) {
            session(['app_locale' => $locale]);
        }

        return back();
    }
}
