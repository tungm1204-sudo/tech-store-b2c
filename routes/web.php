<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\AiAdvisorController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;

/*
|--------------------------------------------------------------------------
| TechStore B2C - Web Routes
|--------------------------------------------------------------------------
| QUY TẮC CHỐNG XUNG ĐỘT (CONFLICT) KHI GHÉP CODE:
|   - Mỗi thành viên CHỈ thêm route vào ĐÚNG VÙNG của mình bên dưới.
|   - Không sửa, không xóa, không di chuyển route trong vùng của người khác.
|   - Route trang quản trị: tự mở 1 group admin riêng ngay trong vùng của mình:
|       Route::prefix('admin')->name('admin.')
|           ->middleware(['auth', 'role:staff,admin'])
|           ->group(function () { ... });
*/

/* ========================================================================
 | 0. DÙNG CHUNG (Tự đã dựng sẵn ở Ngày 1-2) - KHÔNG SỬA
 * ======================================================================== */

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/locale/{locale}', [HomeController::class, 'switchLocale'])->name('locale.switch');

// Đăng nhập nhanh để test khi chưa có trang Login (chỉ bật khi APP_ENV=local)
if (app()->environment('local')) {
    require __DIR__ . '/dev.php';
}

/* ========================================================================
 | 1. ĐIỀN - Xác thực, Phân quyền RBAC, Hồ sơ & Sổ địa chỉ
 |    Gợi ý tên route: login, register, logout, password.request,
 |                     profile.index, admin.users.index ...
 * ======================================================================== */


/* ========================================================================
 | 2. TUYẾN - Danh mục, Sản phẩm, Tìm kiếm & Bộ lọc nâng cao
 |    Gợi ý tên route: products.index, products.show (theo slug),
 |                     admin.products.*, admin.catalog.index ...
 * ======================================================================== */

Route::get('/products', [CatalogController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [CatalogController::class, 'show'])->name('products.show');

Route::prefix('admin')->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::resource('categories', AdminCategoryController::class)->except(['show']);
        Route::resource('brands', AdminBrandController::class)->except(['show']);
        Route::resource('products', AdminProductController::class)->except(['show']);
    });



/* ========================================================================
 | 3. PHÚC - Giỏ hàng, Mã giảm giá, Thanh toán (COD / QR)
 |    Gợi ý tên route: cart.index, cart.add, checkout.index,
 |                     checkout.store, admin.coupons.index ...
 * ======================================================================== */
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'add'])->name('cart.add');
Route::patch('/cart/items/{item}', [CartController::class, 'update'])->whereNumber('item')->name('cart.update');
Route::delete('/cart/items/{item}', [CartController::class, 'remove'])->whereNumber('item')->name('cart.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/checkout/orders/{order}/payment', [CheckoutController::class, 'payment'])->whereNumber('order')->name('checkout.payment');
Route::get('/checkout/orders/{order}/result', [CheckoutController::class, 'result'])->whereNumber('order')->name('checkout.result');
Route::post('/checkout/orders/{order}/mock-paid', [CheckoutController::class, 'mockPaid'])->whereNumber('order')->name('checkout.mock-paid');

if (app()->environment('local')) {
    Route::get('/dev/shop', [\App\Http\Controllers\DevShopController::class, 'index'])->name('dev.shop');
}

Route::prefix('admin')->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::resource('coupons', \App\Http\Controllers\Admin\CouponController::class)->except(['show']);
    });

/* ========================================================================
 | 4. TRUNG ANH - Quản lý Đơn hàng, Đổi trả / Bảo hành, Nhập kho
 |    Gợi ý tên route: orders.index, orders.show, warranties.index,
 |                     admin.orders.*, admin.warranties.*, admin.stock_receipts.*
 * ======================================================================== */
 // --- Khách hàng: đơn hàng của tôi & Đổi trả / Bảo hành ---
Route::middleware('auth')->group(function () {
    Route::get('/orders', 'App\\Http\\Controllers\\OrderController@index')->name('orders.index');
    Route::get('/orders/{order}', 'App\\Http\\Controllers\\OrderController@show')->whereNumber('order')->name('orders.show');
    Route::post('/orders/{order}/cancel', 'App\\Http\\Controllers\\OrderController@cancel')->whereNumber('order')->name('orders.cancel');
    Route::post('/orders/{order}/received', 'App\\Http\\Controllers\\OrderController@received')->whereNumber('order')->name('orders.received');

    Route::get('/warranties', 'App\\Http\\Controllers\\RMAController@index')->name('warranties.index');
    Route::get('/warranties/create', 'App\\Http\\Controllers\\RMAController@create')->name('warranties.create');
    Route::post('/warranties', 'App\\Http\\Controllers\\RMAController@store')->name('warranties.store');
    Route::get('/warranties/{warranty}', 'App\\Http\\Controllers\\RMAController@show')->whereNumber('warranty')->name('warranties.show');
});

// --- Nhân viên / Admin: xử lý đơn, RMA, nhập kho ---
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:staff,admin'])->group(function () {
    Route::get('/orders', 'App\\Http\\Controllers\\Admin\\OrderManagementController@index')->name('orders.index');
    Route::get('/orders/{order}', 'App\\Http\\Controllers\\Admin\\OrderManagementController@show')->whereNumber('order')->name('orders.show');
    Route::post('/orders/{order}/transition', 'App\\Http\\Controllers\\Admin\\OrderManagementController@transition')->whereNumber('order')->name('orders.transition');
    Route::post('/orders/{order}/mark-paid', 'App\\Http\\Controllers\\Admin\\OrderManagementController@markPaid')->whereNumber('order')->name('orders.mark_paid');

    Route::get('/warranties', 'App\\Http\\Controllers\\Admin\\WarrantyManagementController@index')->name('warranties.index');
    Route::get('/warranties/{warranty}', 'App\\Http\\Controllers\\Admin\\WarrantyManagementController@show')->whereNumber('warranty')->name('warranties.show');
    Route::post('/warranties/{warranty}/transition', 'App\\Http\\Controllers\\Admin\\WarrantyManagementController@transition')->whereNumber('warranty')->name('warranties.transition');

    Route::get('/stock-receipts', 'App\\Http\\Controllers\\Admin\\StockReceiptController@index')->name('stock_receipts.index');
    Route::get('/stock-receipts/create', 'App\\Http\\Controllers\\Admin\\StockReceiptController@create')->name('stock_receipts.create');
    Route::post('/stock-receipts', 'App\\Http\\Controllers\\Admin\\StockReceiptController@store')->name('stock_receipts.store');
    Route::get('/stock-receipts/{stock_receipt}', 'App\\Http\\Controllers\\Admin\\StockReceiptController@show')->whereNumber('stock_receipt')->name('stock_receipts.show');
    Route::post('/stock-receipts/{stock_receipt}/cancel', 'App\\Http\\Controllers\\Admin\\StockReceiptController@cancel')->whereNumber('stock_receipt')->name('stock_receipts.cancel');
    // Chỉ Admin được duyệt phiếu (service cũng kiểm tra lại)
    Route::post('/stock-receipts/{stock_receipt}/approve', 'App\\Http\\Controllers\\Admin\\StockReceiptController@approve')->middleware('role:admin')->whereNumber('stock_receipt')->name('stock_receipts.approve');
});

/* ========================================================================
 | 5. TỰ - Đánh giá & Bình luận, Thống kê doanh thu, Trợ lý AI
 * ======================================================================== */

// Đánh giá & Bình luận sản phẩm (kèm huy hiệu Verified Purchase)
Route::get('/products/{product:slug}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth');
Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy')->middleware('auth');

// Trợ lý AI tư vấn thiết bị công nghệ
Route::get('/ai-advisor', [AiAdvisorController::class, 'index'])->name('ai_advisor.index');

// Quản trị Admin: Dashboard thống kê, Xuất báo cáo CSV & Quản lý duyệt đánh giá
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:staff,admin'])->group(function () {
    Route::get('/dashboard', [AdminAnalyticsController::class, 'index'])->name('dashboard');
    Route::get('/reports/export', [AdminAnalyticsController::class, 'export'])->name('reports.export');
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/reply', [AdminReviewController::class, 'reply'])->name('reviews.reply');
    Route::patch('/reviews/{review}/toggle', [AdminReviewController::class, 'toggleApproval'])->name('reviews.toggle');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
});
