<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Middleware Chuyển đổi Ngôn ngữ VI / EN (Phụ trách: Nguyễn Minh Tự)
 */
class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $locale = session('app_locale', config('app.locale', 'vi'));
        if (in_array($locale, ['vi', 'en'], true)) {
            App::setLocale($locale);
        }
        return $next($request);
    }
}
