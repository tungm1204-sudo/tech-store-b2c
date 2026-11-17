<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            // Khi nhánh hiện tại chưa có trang Login của Điền thì quay về trang chủ (có nút đăng nhập nhanh DEV)
            return ts_route(['login', 'home']);
        }
    }
}
