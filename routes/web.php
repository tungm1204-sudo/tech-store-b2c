<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\AiAdvisorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

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


/* ========================================================================
 | 3. PHÚC - Giỏ hàng, Mã giảm giá, Thanh toán (COD / QR)
 |    Gợi ý tên route: cart.index, cart.add, checkout.index,
 |                     checkout.store, admin.coupons.index ...
 * ======================================================================== */


/* ========================================================================
 | 4. TRUNG ANH - Quản lý Đơn hàng, Đổi trả / Bảo hành, Nhập kho
 |    Gợi ý tên route: orders.index, orders.show, warranties.index,
 |                     admin.orders.*, admin.warranties.*, admin.stock_receipts.*
 * ======================================================================== */


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
