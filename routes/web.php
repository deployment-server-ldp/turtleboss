<?php

use App\Http\Controllers as C;
use App\Http\Controllers\Admin as A;
use Illuminate\Support\Facades\Route;

// ---------- Storefront ----------
Route::get('/', [C\HomeController::class, 'index'])->name('home');
Route::get('/shop', [C\ShopController::class, 'index'])->name('shop');
Route::get('/category/{slug}', [C\ShopController::class, 'category'])->name('category.show');
Route::get('/search/suggest', [C\ShopController::class, 'suggest'])->middleware('throttle:60,1')->name('search.suggest');
Route::get('/product/{slug}', [C\ProductController::class, 'show'])->name('product.show');
Route::get('/product/{slug}/quick', [C\ProductController::class, 'quick'])->name('product.quick');
Route::post('/product/{slug}/review', [C\ProductController::class, 'review'])->middleware('throttle:5,1')->name('product.review');

Route::get('/cart', [C\CartController::class, 'index'])->name('cart');
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/data', [C\CartController::class, 'data'])->name('data');
    Route::get('/quote', [C\CartController::class, 'quote'])->name('quote');
    Route::post('/add', [C\CartController::class, 'add'])->middleware('throttle:60,1')->name('add');
    Route::post('/update', [C\CartController::class, 'update'])->name('update');
    Route::post('/variant', [C\CartController::class, 'variant'])->name('variant');
    Route::post('/remove', [C\CartController::class, 'remove'])->name('remove');
    Route::post('/coupon', [C\CartController::class, 'coupon'])->middleware('throttle:20,1')->name('coupon');
    Route::delete('/coupon', [C\CartController::class, 'removeCoupon'])->name('coupon.remove');
});
Route::get('/checkout', [C\CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [C\CheckoutController::class, 'place'])->middleware('throttle:10,1')->name('checkout.place');
Route::get('/order/{number}/confirmed', [C\CheckoutController::class, 'success'])->name('checkout.success');

Route::get('/wishlist', [C\WishlistController::class, 'index'])->name('wishlist');
Route::post('/wishlist/toggle', [C\WishlistController::class, 'toggle'])->name('wishlist.toggle');

Route::post('/newsletter', [C\NewsletterController::class, 'subscribe'])->middleware('throttle:10,1')->name('newsletter');
Route::post('/contact', [C\NewsletterController::class, 'contact'])->middleware('throttle:5,1')->name('contact');

Route::get('/blog', [C\BlogController::class, 'index'])->name('blog');
Route::get('/blog/category/{slug}', [C\BlogController::class, 'index'])->defaults('kind', 'category')->name('blog.category');
Route::get('/blog/tag/{slug}', [C\BlogController::class, 'index'])->defaults('kind', 'tag')->name('blog.tag');
Route::get('/blog/{slug}', [C\BlogController::class, 'show'])->name('blog.show');

Route::get('/sitemap.xml', [C\SeoController::class, 'sitemap']);
Route::get('/robots.txt', [C\SeoController::class, 'robots']);

// ---------- Customer auth & account ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [C\AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [C\AuthController::class, 'login'])->middleware('throttle:8,1');
    Route::get('/register', [C\AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [C\AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::get('/forgot-password', [C\AuthController::class, 'forgotForm'])->name('password.request');
    Route::post('/forgot-password', [C\AuthController::class, 'sendReset'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [C\AuthController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [C\AuthController::class, 'reset'])->name('password.update');
});
Route::post('/logout', [C\AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->prefix('account')->name('account')->group(function () {
    Route::get('/', [C\AccountController::class, 'index'])->name('');
    Route::get('/orders', [C\AccountController::class, 'orders'])->name('.orders');
    Route::get('/orders/{number}', [C\AccountController::class, 'order'])->name('.order');
    Route::get('/profile', [C\AccountController::class, 'profile'])->name('.profile');
    Route::put('/profile', [C\AccountController::class, 'updateProfile'])->name('.profile.update');
    Route::put('/password', [C\AccountController::class, 'password'])->name('.password');
    Route::get('/addresses', [C\AccountController::class, 'addresses'])->name('.addresses');
    Route::post('/addresses', [C\AccountController::class, 'saveAddress'])->name('.addresses.store');
    Route::put('/addresses/{id}', [C\AccountController::class, 'saveAddress'])->name('.addresses.update');
    Route::delete('/addresses/{id}', [C\AccountController::class, 'deleteAddress'])->name('.addresses.delete');
});

// ---------- Admin ----------
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', fn () => auth()->check() && auth()->user()->isStaff() ? redirect()->route('admin.dashboard') : view('admin.login'))->name('login');
    Route::post('/login', [C\AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.post');

    Route::middleware('staff')->group(function () {
        Route::get('/', [A\DashboardController::class, 'index'])->middleware('staff:dashboard')->name('dashboard');

        Route::middleware('staff:products')->group(function () {
            Route::get('products/export', [A\ProductController::class, 'export'])->name('products.export');
            Route::get('products/sample-csv', [A\ProductController::class, 'sampleCsv'])->name('products.sample');
            Route::post('products/import', [A\ProductController::class, 'import'])->name('products.import');
            Route::post('products/bulk', [A\ProductController::class, 'bulk'])->name('products.bulk');
            Route::post('products/{product}/duplicate', [A\ProductController::class, 'duplicate'])->name('products.duplicate');
            Route::post('products/{product}/toggle', [A\ProductController::class, 'toggle'])->name('products.toggle');
            Route::resource('products', A\ProductController::class)->except('show');
            Route::resource('attributes', A\AttributeController::class)->except('show');
        });
        Route::middleware('staff:categories')->resource('categories', A\CategoryController::class)->except('show');

        Route::middleware('staff:orders')->group(function () {
            Route::get('orders', [A\OrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [A\OrderController::class, 'show'])->name('orders.show');
            Route::post('orders/{order}/status', [A\OrderController::class, 'status'])->name('orders.status');
            Route::post('orders/{order}/tracking', [A\OrderController::class, 'tracking'])->name('orders.tracking');
            Route::post('orders/{order}/note', [A\OrderController::class, 'note'])->name('orders.note');
            Route::post('orders/{order}/payment', [A\OrderController::class, 'payment'])->name('orders.payment');
            Route::post('orders/{order}/refund', [A\OrderController::class, 'refund'])->name('orders.refund');
            Route::get('orders/{order}/invoice', [A\OrderController::class, 'invoice'])->name('orders.invoice');
            Route::get('orders/{order}/invoice.pdf', [A\OrderController::class, 'pdf'])->name('orders.pdf');
        });
        Route::middleware('staff:customers')->group(function () {
            Route::get('customers/export', [A\CustomerController::class, 'export'])->name('customers.export');
            Route::get('customers', [A\CustomerController::class, 'index'])->name('customers.index');
            Route::get('customers/{customer}', [A\CustomerController::class, 'show'])->name('customers.show');
            Route::put('customers/{customer}', [A\CustomerController::class, 'update'])->name('customers.update');
            Route::get('inbox', [A\BackupController::class, 'inbox'])->name('inbox');
            Route::post('inbox/{message}/read', [A\BackupController::class, 'readMessage'])->name('inbox.read');
            Route::delete('inbox/{message}', [A\BackupController::class, 'deleteMessage'])->name('inbox.delete');
            Route::get('subscribers/export', [A\BackupController::class, 'exportSubscribers'])->name('subscribers.export');
        });
        Route::middleware('staff:inventory')->prefix('inventory')->name('inventory.')->group(function () {
            Route::get('/', [A\InventoryController::class, 'index'])->name('index');
            Route::post('/adjust', [A\InventoryController::class, 'adjust'])->name('adjust');
            Route::get('/history', [A\InventoryController::class, 'history'])->name('history');
        });
        Route::middleware('staff:coupons')->resource('coupons', A\CouponController::class)->except('show');
        Route::middleware('staff:reviews')->group(function () {
            Route::get('reviews', [A\ReviewController::class, 'index'])->name('reviews.index');
            Route::put('reviews/{review}', [A\ReviewController::class, 'update'])->name('reviews.update');
            Route::delete('reviews/{review}', [A\ReviewController::class, 'destroy'])->name('reviews.destroy');
            Route::get('wishlist-analytics', [A\WishlistAnalyticsController::class, 'index'])->name('wishlist');
        });

        Route::middleware('staff:content')->group(function () {
            Route::get('homepage', [A\HomepageController::class, 'index'])->name('homepage.index');
            Route::post('homepage', [A\HomepageController::class, 'store'])->name('homepage.store');
            Route::post('homepage/reorder', [A\HomepageController::class, 'reorder'])->name('homepage.reorder');
            Route::get('homepage/{section}', [A\HomepageController::class, 'edit'])->name('homepage.edit');
            Route::put('homepage/{section}', [A\HomepageController::class, 'update'])->name('homepage.update');
            Route::post('homepage/{section}/toggle', [A\HomepageController::class, 'toggle'])->name('homepage.toggle');
            Route::delete('homepage/{section}', [A\HomepageController::class, 'destroy'])->name('homepage.destroy');
            Route::resource('sliders', A\SliderController::class)->except('show');
            Route::get('menus', [A\MenuController::class, 'index'])->name('menus.index');
            Route::post('menus', [A\MenuController::class, 'store'])->name('menus.store');
            Route::get('menus/{menu}', [A\MenuController::class, 'edit'])->name('menus.edit');
            Route::delete('menus/{menu}', [A\MenuController::class, 'destroy'])->name('menus.destroy');
            Route::post('menus/{menu}/items', [A\MenuController::class, 'addItem'])->name('menus.items.add');
            Route::put('menu-items/{item}', [A\MenuController::class, 'updateItem'])->name('menus.items.update');
            Route::post('menu-items/{item}/move', [A\MenuController::class, 'move'])->name('menus.items.move');
            Route::delete('menu-items/{item}', [A\MenuController::class, 'deleteItem'])->name('menus.items.delete');
            Route::resource('pages', A\PageController::class)->except('show');
            Route::resource('blog', A\BlogPostController::class)->except('show')->parameters(['blog' => 'blog']);
            Route::resource('blog-categories', A\BlogCategoryController::class)->except('show');
            Route::resource('blog-tags', A\BlogTagController::class)->except('show');
            Route::get('media', [A\MediaController::class, 'index'])->name('media.index');
            Route::post('media', [A\MediaController::class, 'store'])->name('media.store');
            Route::put('media/{medium}', [A\MediaController::class, 'update'])->name('media.update');
            Route::delete('media/{medium}', [A\MediaController::class, 'destroy'])->name('media.destroy');
        });

        Route::middleware('staff:seo')->group(function () {
            Route::get('seo', [A\SeoOverviewController::class, 'index'])->name('seo');
            Route::post('seo/sitemap', [A\SeoOverviewController::class, 'regenerate'])->name('seo.sitemap');
            Route::resource('redirects', A\RedirectController::class)->except('show');
        });
        Route::resource('shipping-rules', A\ShippingRuleController::class)->except('show')->middleware('staff:settings');
        Route::get('settings/{group}', [A\SettingController::class, 'show'])->name('settings.show');
        Route::put('settings/{group}', [A\SettingController::class, 'update'])->name('settings.update');

        Route::middleware('staff:users')->resource('users', A\UserController::class)->except('show');
        Route::middleware('staff:reports')->get('reports', [A\ReportController::class, 'index'])->name('reports');
        Route::middleware('staff:backup')->prefix('maintenance')->name('backup.')->group(function () {
            Route::get('/', [A\BackupController::class, 'index'])->name('index');
            Route::post('/backup', [A\BackupController::class, 'create'])->name('create');
            Route::get('/backup/{file}', [A\BackupController::class, 'download'])->name('download');
            Route::delete('/backup/{file}', [A\BackupController::class, 'delete'])->name('delete');
            Route::post('/cache', [A\BackupController::class, 'clearCache'])->name('cache');
            Route::post('/mode', [A\BackupController::class, 'maintenance'])->name('mode');
        });
    });
});

// ---------- CMS pages (must stay last: /{slug}) ----------
Route::get('/{slug}', [C\PageController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('page');
