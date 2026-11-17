<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ĐĂNG NHẬP NHANH DÀNH CHO GIAI ĐOẠN CODE SONG SONG (CHỈ CHẠY KHI APP_ENV=local)
|--------------------------------------------------------------------------
| Trong lúc Điền chưa hoàn thành trang Đăng nhập, các thành viên khác vẫn cần
| đăng nhập để test phần của mình (giỏ hàng, đơn hàng, trang admin...).
| Truy cập: /dev/login-as/admin  |  /dev/login-as/staff  |  /dev/login-as/customer
| File này KHÔNG được nạp khi APP_ENV=production.
*/

Route::get('/dev/login-as/{role}', function (string $role) {
    abort_unless(in_array($role, ['admin', 'staff', 'customer'], true), 404);

    $user = User::where('role', $role)->orderBy('id')->firstOrFail();
    Auth::login($user);
    request()->session()->regenerate();

    $target = $role === 'customer' ? route('home') : ts_route(['admin.dashboard', 'home']);

    return redirect($target)->with('success', "[DEV] Đã đăng nhập nhanh với vai trò {$role}: {$user->email}");
})->name('dev.login');

Route::post('/dev/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home')->with('success', '[DEV] Đã đăng xuất.');
})->name('dev.logout');
