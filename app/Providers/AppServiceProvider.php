<?php

namespace App\Providers;

use App\Http\Controllers\WishlistController;
use App\Models\Category;
use App\Models\Menu;
use App\Services\CartService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::defaultView('partials.pagination');
        View::composer('layouts.app', function ($view) {
            $cart = app(CartService::class)->cart();
            $count = $cart ? (int) $cart->items()->sum('quantity') : 0;
            $view->with([
                'navCategories' => Cache::remember('nav.categories', 600, fn () => Category::active()->roots()->with(['children' => fn ($q) => $q->where('is_active', true)])->orderBy('sort_order')->get()),
                'headerMenu' => Menu::tree('header'),
                'footerHelp' => Menu::tree('footer_help'),
                'footerShop' => Menu::tree('footer_shop'),
                'boot' => ['cart' => ['count' => $count], 'wish' => WishlistController::ids()],
            ]);
        });
    }
}
