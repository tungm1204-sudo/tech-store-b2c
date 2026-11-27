<?php

use App\Http\Controllers\Api\V1\TechStoreApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RESTful API v1 (tiền tố /api) - mỗi thành viên thêm vào vùng của mình
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    /* ---------- 5. TỰ - API sản phẩm / thống kê / AI ---------- */
    Route::get('/products', [TechStoreApiController::class, 'products']);
    Route::get('/products/{slug}', [TechStoreApiController::class, 'showProduct']);
    Route::get('/stats', [TechStoreApiController::class, 'stats']);
    Route::post('/ai-advisor/recommend', [TechStoreApiController::class, 'recommend']);
    Route::get('/orders/{orderCode}', [TechStoreApiController::class, 'trackOrder']);
});
