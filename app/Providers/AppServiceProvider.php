<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\CartService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\Order;
use App\Observers\OrderObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        Paginator::useBootstrapFive();
        Order::observe(OrderObserver::class);

        View::composer('layouts.*', function ($view) {
            try {
                // CartService do Phúc viết - nhánh nào chưa có thì đếm = 0
                $cartCount = class_exists(CartService::class) ? app(CartService::class)->getCount() : 0;
                $compareCount = count(session('compare_ids', []));
                $navCategories = Category::where('is_active', true)->get(['id', 'name', 'slug', 'icon']);
            } catch (\Throwable $e) {
                $cartCount = 0;
                $compareCount = 0;
                $navCategories = collect();
            }

            $view->with(compact('cartCount', 'compareCount', 'navCategories'));
        });
    }
}
