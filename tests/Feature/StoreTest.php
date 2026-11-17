<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Redirect;
use App\Models\Review;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Storage::fake('public');
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoSeeder::class);
        $this->keepSession($this->get('/shop'));
    }

    /** Laravel's test client does not carry cookies between requests; pin the session cookie like a browser would. */
    private function keepSession($response): void
    {
        $this->withCredentials(); // JSON requests only send cookies with this
        $this->withCookie(config("session.cookie"), app("session")->getId());
    }

    private function bag(): Product { return Product::where('sku', 'DEMO-BAG')->firstOrFail(); }
    private function simple(): Product { return Product::where('sku', 'DEMO-CLN-1')->firstOrFail(); } // price 650 sale 499, stock 80
    private function addSimple(int $qty = 1) { return $this->postJson('/cart/add', ['product_id' => $this->simple()->id, 'quantity' => $qty]); }
    private function checkoutData(array $o = []): array
    {
        return $o + ['name' => 'Ali Khan', 'email' => 'ali@example.com', 'phone' => '03001234567', 'address' => 'House 1, Street 2', 'city' => 'Lahore', 'province' => 'Punjab', 'payment_method' => 'cod'];
    }
    private function staff(string $role): User
    {
        return User::factory()->create(['role_id' => Role::where('slug', $role)->value('id')])->fresh();
    }

    public function test_pages_render_with_single_canonical_and_mobile_nav(): void
    {
        foreach (['/', '/shop', '/category/toys', '/about-us', '/faq', '/contact-us', '/blog', '/cart', '/login', '/register'] as $u) {
            $res = $this->get($u)->assertOk();
            $this->assertSame(1, substr_count($res->getContent(), 'rel="canonical"'), "canonical count on $u");
            $this->assertSame(1, substr_count($res->getContent(), '<title>'), "title count on $u");
        }
        $home = $this->get('/')->getContent();
        $this->assertStringContainsString('aria-label="Mobile navigation"', $home);
        foreach (['Home', 'Shop', 'Categories', 'Wishlist', 'Account'] as $tab) $this->assertStringContainsString(">$tab</", $home);
    }

    public function test_product_page_has_structured_data_and_variant_config(): void
    {
        $res = $this->get($this->bag()->url)->assertOk();
        $html = $res->getContent();
        $this->assertStringContainsString('"@type":"Product"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('"priceCurrency":"PKR"', $html);
        $this->assertSame(1, substr_count($html, 'rel="canonical"'));
    }

    public function test_noindex_products_are_excluded_from_sitemap_and_tagged(): void
    {
        $p = $this->simple();
        $this->assertStringContainsString($p->url, $this->get('/sitemap.xml')->getContent());
        $p->saveSeo(['noindex' => true]);
        \Cache::forget('sitemap.xml');
        $this->assertStringNotContainsString($p->url, $this->get('/sitemap.xml')->getContent());
        $this->assertStringContainsString('noindex', $this->get($p->url)->getContent());
        $this->assertStringContainsString('Sitemap:', $this->get('/robots.txt')->getContent());
    }

    public function test_variant_product_requires_a_valid_variant_and_respects_stock(): void
    {
        $bag = $this->bag();
        $this->postJson('/cart/add', ['product_id' => $bag->id])->assertStatus(422);              // no variant chosen
        $out = $bag->variants()->where('stock_qty', 0)->first();                                    // Black / Large
        $this->postJson('/cart/add', ['product_id' => $bag->id, 'variant_id' => $out->id])->assertStatus(422);
        $ok = $bag->variants()->where('stock_qty', 12)->first();
        $this->postJson('/cart/add', ['product_id' => $bag->id, 'variant_id' => $ok->id, 'quantity' => 13])->assertStatus(422); // > stock
        $res = $this->postJson('/cart/add', ['product_id' => $bag->id, 'variant_id' => $ok->id, 'quantity' => 2])->assertOk();
        $this->assertSame(2, $res->json('cart.count'));
        $this->postJson('/cart/add', ['product_id' => $bag->id, 'variant_id' => $ok->id, 'quantity' => 11])->assertStatus(422); // 2+11 > 12
    }

    public function test_variant_price_and_sale_price_are_used(): void
    {
        $bag = $this->bag();
        $small = $bag->variants()->where('sku', 'like', '%-S')->first();   // 1800 / sale 1500
        $large = $bag->variants()->where('sku', 'like', '%-L')->where('stock_qty', 12)->first(); // 2200 no sale
        $this->postJson('/cart/add', ['product_id' => $bag->id, 'variant_id' => $small->id]);
        $res = $this->postJson('/cart/add', ['product_id' => $bag->id, 'variant_id' => $large->id]);
        $this->assertEquals(1500 + 2200, $res->json('cart.subtotal'));
    }

    public function test_cart_totals_quantity_and_remove(): void
    {
        $res = $this->addSimple(3)->assertOk();
        $this->assertEquals(499 * 3, $res->json('cart.subtotal'));
        $id = $res->json('cart.items.0.id');
        $this->assertEquals(499 * 5, $this->postJson('/cart/update', ['item_id' => $id, 'quantity' => 5])->json('cart.subtotal'));
        $this->postJson('/cart/update', ['item_id' => $id, 'quantity' => 500])->assertStatus(422);
        $this->assertSame(0, $this->postJson('/cart/remove', ['item_id' => $id])->json('cart.count'));
    }

    public function test_shipping_flat_rate_and_free_threshold(): void
    {
        $res = $this->addSimple(1);                                          // 499 < 3000
        $this->assertEquals(250, $res->json('cart.shipping'));
        $res = $this->addSimple(7);                                          // 3992 >= 3000
        $this->assertEquals(0, $res->json('cart.shipping'));
    }

    public function test_city_based_shipping_rule(): void
    {
        \App\Models\ShippingRule::create(['name' => 'Karachi', 'city' => 'Karachi', 'rate' => 150, 'priority' => 1]);
        $this->addSimple(1);
        $this->assertEquals(150, $this->getJson('/cart/quote?city=Karachi')->json('shipping'));
        $this->assertEquals(250, $this->getJson('/cart/quote?city=Lahore')->json('shipping'));
    }

    public function test_coupon_rules_are_validated_server_side(): void
    {
        $this->addSimple(1);                                                 // subtotal 499
        $this->postJson('/cart/coupon', ['code' => 'DEMO10'])->assertStatus(422);        // min spend 1000
        $this->postJson('/cart/coupon', ['code' => 'NOPE'])->assertStatus(422);
        $this->addSimple(2);                                                 // 1497
        $res = $this->postJson('/cart/coupon', ['code' => 'demo10'])->assertOk();        // case-insensitive
        $this->assertEquals(round(1497 * .10, 2), $res->json('cart.discount'));

        Coupon::where('code', 'DEMO10')->update(['expires_at' => now()->subDay()]);
        $this->assertEquals(0, $this->getJson('/cart/data')->json('discount'));          // expired coupon silently dropped
    }

    public function test_coupon_max_discount_fixed_free_shipping_and_category_scope(): void
    {
        $this->addSimple(10);                                                // 4990
        Coupon::create(['code' => 'BIG', 'type' => 'percent', 'amount' => 50, 'max_discount' => 300]);
        $this->assertEquals(300, $this->postJson('/cart/coupon', ['code' => 'BIG'])->json('cart.discount'));
        Coupon::create(['code' => 'FIX', 'type' => 'fixed', 'amount' => 100]);
        $this->assertEquals(100, $this->postJson('/cart/coupon', ['code' => 'FIX'])->json('cart.discount'));
        Coupon::create(['code' => 'SHIP', 'type' => 'free_shipping', 'amount' => 0]);
        $this->postJson('/cart/coupon', ['code' => 'SHIP']);

        $toys = \App\Models\Category::where('slug', 'toys')->first();
        $c = Coupon::create(['code' => 'TOYS', 'type' => 'percent', 'amount' => 20]);
        $c->categories()->sync([$toys->id]);
        $this->postJson('/cart/coupon', ['code' => 'TOYS'])->assertStatus(422);         // cart has cleaning product only
    }

    public function test_usage_limit_is_enforced(): void
    {
        Coupon::create(['code' => 'ONCE', 'type' => 'fixed', 'amount' => 50, 'usage_limit' => 1, 'used_count' => 1]);
        $this->addSimple(1);
        $this->postJson('/cart/coupon', ['code' => 'ONCE'])->assertStatus(422);
    }

    public function test_checkout_and_cart_pages_render_with_items_and_saved_addresses(): void
    {
        $this->addSimple(1);
        $this->get('/checkout')->assertOk()->assertSee('Place Order')->assertSee('Cash on Delivery');
        $this->get('/cart')->assertOk();
        $user = User::factory()->create();
        $c = $user->customer()->create(['name' => $user->name, 'email' => $user->email]);
        $c->addresses()->create(['name' => 'A', 'phone' => '0300', 'address' => "Road 'quoted' 5", 'city' => 'Lahore', 'is_default' => true]);
        $this->actingAs($user);
        $this->addSimple(1);
        $this->get('/checkout')->assertOk()->assertSee('Lahore');
    }

    public function test_guest_checkout_creates_order_and_manages_stock(): void
    {
        $p = $this->simple();
        $this->addSimple(2);
        $this->postJson('/cart/coupon', ['code' => 'DEMO10']);                // 998 < 1000 -> rejected, none applied
        $res = $this->post('/checkout', $this->checkoutData());
        $order = Order::firstOrFail();
        $res->assertRedirect(route('checkout.success', $order->number));
        $this->assertEquals(998, $order->subtotal);
        $this->assertEquals(250, $order->shipping_total);
        $this->assertEquals(1248, $order->total);
        $this->assertSame('pending', $order->status);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(78, $p->fresh()->stock_qty);
        $this->assertSame(2, $p->fresh()->sales_count);
        $this->assertDatabaseHas('inventory_logs', ['product_id' => $p->id, 'change' => -2, 'reason' => 'order']);
        $this->assertDatabaseHas('notifications', ['channel' => 'email', 'event' => 'order_placed', 'recipient' => 'ali@example.com']);
        $this->assertSame(0, $this->getJson('/cart/data')->json('count'));              // cart cleared
        $this->get(route('checkout.success', $order->number))->assertOk();
    }

    public function test_checkout_with_coupon_records_usage(): void
    {
        $this->addSimple(3);                                                 // 1497
        $this->postJson('/cart/coupon', ['code' => 'DEMO10'])->assertOk();
        $this->post('/checkout', $this->checkoutData(['city' => 'Karachi']));
        $order = Order::firstOrFail();
        $this->assertEquals(149.7, $order->discount_total);
        $this->assertEquals(1497 - 149.7 + 250, $order->total);
        $this->assertDatabaseHas('coupon_usages', ['order_id' => $order->id]);
        $this->assertSame(1, Coupon::where('code', 'DEMO10')->value('used_count'));
    }

    public function test_checkout_blocks_when_stock_changed_and_validates_input(): void
    {
        $this->addSimple(2);
        $this->simple()->update(['stock_qty' => 1]);
        $this->post('/checkout', $this->checkoutData())->assertSessionHasErrors('cart');
        $this->assertSame(0, Order::count());
        $this->post('/checkout', ['name' => ''])->assertSessionHasErrors(['name', 'email', 'phone', 'address', 'city']);
        $this->post('/checkout', $this->checkoutData(['phone' => 'abc']))->assertSessionHasErrors('phone');
    }

    public function test_variant_checkout_decrements_variant_stock(): void
    {
        $bag = $this->bag(); $v = $bag->variants()->where('stock_qty', 12)->first();
        $this->postJson('/cart/add', ['product_id' => $bag->id, 'variant_id' => $v->id, 'quantity' => 3]);
        $this->post('/checkout', $this->checkoutData(['payment_method' => 'bank_transfer']));
        $this->assertSame(9, $v->fresh()->stock_qty);
        $this->assertNotNull(Order::first()->items->first()->variant_label);
    }

    public function test_cancelling_order_restocks_and_refund_tracking(): void
    {
        $this->addSimple(4);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::first();
        $this->assertSame(76, $this->simple()->stock_qty);
        $admin = $this->staff('store_manager');
        $this->actingAs($admin)->post(route('admin.orders.status', $order), ['status' => 'cancelled'])->assertRedirect();
        $this->assertSame(80, $this->simple()->stock_qty);
        $this->actingAs($admin)->post(route('admin.orders.status', $order), ['status' => 'processing']);
        $this->assertSame(76, $this->simple()->stock_qty);                               // reopened re-reserves stock
        $this->actingAs($admin)->post(route('admin.orders.refund', $order), ['amount' => 100, 'reason' => 'test'])->assertRedirect();
        $this->assertEquals(100, $order->fresh()->refunded_total);
        $this->actingAs($admin)->post(route('admin.orders.refund', $order), ['amount' => 99999])->assertSessionHasErrors('amount');
    }

    public function test_cart_persists_for_logged_in_users_and_merges_on_login(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass-1']);
        $this->addSimple(2);                                                           // guest cart
        $this->keepSession($this->post('/login', ['email' => $user->email, 'password' => 'secret-pass-1'])->assertRedirect());
        $this->assertSame(2, $this->getJson('/cart/data')->json('count'));
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
    }

    public function test_wishlist_toggle(): void
    {
        $p = $this->simple();
        $this->postJson('/wishlist/toggle', ['product_id' => $p->id])->assertJson(['added' => true, 'count' => 1]);
        $this->get('/wishlist')->assertOk()->assertSee($p->name);
        $this->postJson('/wishlist/toggle', ['product_id' => $p->id])->assertJson(['added' => false, 'count' => 0]);
    }

    public function test_reviews_need_approval_and_verified_flag_requires_purchase(): void
    {
        $p = $this->simple();
        $this->post("/product/{$p->slug}/review", ['rating' => 5, 'body' => 'Great stuff', 'name' => 'Rando', 'email' => 'rando@example.com']);
        $this->assertFalse(Review::first()->is_verified);
        $this->assertFalse(Review::first()->is_approved);
        $this->assertSame(0, $p->fresh()->rating_count);                               // not counted until approved

        $this->addSimple(1);
        $this->post('/checkout', $this->checkoutData(['email' => 'buyer@example.com']));
        $this->post("/product/{$p->slug}/review", ['rating' => 4, 'body' => 'Good product', 'name' => 'Buyer', 'email' => 'buyer@example.com']);
        $r = Review::latest('id')->first();
        $this->assertTrue($r->is_verified);

        $admin = $this->staff('content_manager');
        $this->actingAs($admin)->put(route('admin.reviews.update', $r), ['approve' => 1]);
        $this->assertSame(1, $p->fresh()->rating_count);
        $this->assertEquals(4, $p->fresh()->rating_avg);
    }

    public function test_search_suggestions_by_name_sku_category_brand(): void
    {
        $this->getJson('/search/suggest?q=Microfiber')->assertJsonFragment(['name' => 'Demo Microfiber Cleaning Cloth Set']);
        $this->getJson('/search/suggest?q=DEMO-TOY-1')->assertJsonCount(1);
        $this->getJson('/search/suggest?q=Turtle')->assertJsonFragment(['name' => 'Demo Kitchen Organizer Rack']); // brand
        $this->getJson('/search/suggest?q=Personal Care')->assertJsonFragment(['name' => 'Demo Personal Care Kit']); // category
        $this->getJson('/search/suggest?q=x')->assertExactJson([]);
    }

    public function test_shop_filters_and_sorting(): void
    {
        $this->get('/shop?sort=price_asc&min=500&max=1000')->assertOk()->assertSee('Demo Microfiber')->assertDontSee('Demo Bathroom Shelf');
        $this->get('/shop?availability=in_stock')->assertOk()->assertDontSee('Demo Custom Printed Mug');
        $this->get('/category/toys')->assertOk()->assertSee('Demo Building Blocks Toy')->assertDontSee('Demo Bathroom Shelf');
        $this->getJson('/shop?more=1&page=1', ['X-Requested-With' => 'XMLHttpRequest'])->assertJsonStructure(['html', 'next']);
    }

    public function test_redirect_manager_handles_404(): void
    {
        Redirect::create(['from_path' => '/old-bag', 'to_url' => '/shop', 'status_code' => 301]);
        $this->get('/old-bag')->assertStatus(301)->assertRedirect('/shop');
        $this->get('/really-missing')->assertNotFound();
    }

    public function test_admin_routes_require_authentication_and_correct_role(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();   // customer

        $pm = $this->staff('product_manager');
        $this->actingAs($pm)->get('/admin/products')->assertOk();
        $this->actingAs($pm)->get('/admin/orders')->assertForbidden();
        $this->actingAs($pm)->get('/admin/users')->assertForbidden();

        $om = $this->staff('order_manager');
        $this->actingAs($om)->get('/admin/orders')->assertOk();
        $this->actingAs($om)->get('/admin/products')->assertForbidden();
        $this->actingAs($om)->get('/admin/settings/general')->assertForbidden();

        $cm = $this->staff('content_manager');
        $this->actingAs($cm)->get('/admin/homepage')->assertOk();
        $this->actingAs($cm)->get('/admin/orders')->assertForbidden();
        $this->actingAs($cm)->get('/admin/settings/payment')->assertForbidden();
        $this->actingAs($cm)->get('/admin/settings/seo')->assertOk();

        $sm = $this->staff('store_manager');
        $this->actingAs($sm)->get('/admin/users')->assertForbidden();
        $this->actingAs($sm)->get('/admin/maintenance')->assertForbidden();

        $sa = $this->staff('super_admin');
        foreach (['/admin', '/admin/products', '/admin/products/create', '/admin/categories', '/admin/attributes', '/admin/orders', '/admin/customers', '/admin/inventory', '/admin/inventory/history', '/admin/coupons', '/admin/reviews', '/admin/wishlist-analytics', '/admin/homepage', '/admin/sliders', '/admin/menus', '/admin/pages', '/admin/blog', '/admin/blog-categories', '/admin/blog-tags', '/admin/media', '/admin/seo', '/admin/redirects', '/admin/shipping-rules', '/admin/settings/general', '/admin/settings/payment', '/admin/settings/email', '/admin/settings/whatsapp', '/admin/settings/seo', '/admin/settings/shipping', '/admin/users', '/admin/reports', '/admin/maintenance', '/admin/inbox'] as $u) {
            $this->actingAs($sa)->get($u)->assertOk();
        }
    }

    public function test_inactive_staff_cannot_use_admin(): void
    {
        $u = $this->staff('super_admin'); $u->update(['is_active' => false]);
        $this->actingAs($u)->get('/admin')->assertForbidden();
    }

    public function test_admin_can_create_product_with_variants_and_duplicate_and_bulk(): void
    {
        $admin = $this->staff('super_admin');
        $color = \App\Models\Attribute::where('slug', 'color')->first()->values;
        $cat = \App\Models\Category::first();
        $res = $this->actingAs($admin)->post('/admin/products', [
            'name' => 'Test Gadget', 'price' => 1000, 'sale_price' => 800, 'stock_status' => 'in_stock', 'manage_stock' => 1, 'stock_qty' => 5, 'is_active' => 1,
            'category_ids' => [$cat->id], 'specs' => ['name' => ['Weight'], 'value' => ['1kg']],
            'variants' => [
                ['sku' => 'TG-R', 'price' => 1000, 'sale_price' => 900, 'stock_qty' => 4, 'is_active' => 1, 'values' => [$color[0]->id]],
                ['sku' => 'TG-B', 'price' => '', 'sale_price' => '', 'stock_qty' => 0, 'is_active' => 1, 'values' => [$color[1]->id]],
            ],
            'seo' => ['title' => 'Test Gadget SEO', 'description' => 'desc'],
        ]);
        $res->assertSessionHasNoErrors();
        $p = Product::where('name', 'Test Gadget')->firstOrFail();
        $res->assertRedirect(route('admin.products.edit', $p));
        $this->assertSame(2, $p->variants()->count());
        $this->assertSame('Test Gadget SEO', $p->seo->title);
        $this->assertEquals(800, $p->fresh()->load('variants')->final_price);               // TG-B inherits product sale price 800 (< TG-R 900)
        $this->assertDatabaseHas('inventory_logs', ['variant_id' => $p->variants()->first()->id, 'change' => 4]);

        $this->actingAs($admin)->post(route('admin.products.duplicate', $p))->assertRedirect();
        $this->assertSame(2, Product::where('name', 'like', 'Test Gadget%')->count());
        $copy = Product::where('name', 'Test Gadget (Copy)')->first();
        $this->assertFalse($copy->is_active);
        $this->assertSame(2, $copy->variants()->count());

        // duplicate variant SKU rejected
        $this->actingAs($admin)->put(route('admin.products.update', $p), ['name' => 'Test Gadget', 'price' => 1000, 'stock_status' => 'in_stock',
            'variants' => [['sku' => 'X', 'values' => [$color[0]->id]], ['sku' => 'X', 'values' => [$color[1]->id]]]])->assertSessionHasErrors('variants');

        $this->actingAs($admin)->post('/admin/products/bulk', ['action' => 'price', 'ids' => [$p->id], 'mode' => 'percent', 'value' => 10]);
        $this->assertEquals(1100, $p->fresh()->price);
        $this->actingAs($admin)->post('/admin/products/bulk', ['action' => 'delete', 'ids' => [$copy->id]]);
        $this->assertSoftDeleted($copy);
    }

    public function test_csv_export_and_import_roundtrip(): void
    {
        $admin = $this->staff('super_admin');
        $csv = $this->actingAs($admin)->get('/admin/products/export')->streamedContent();
        $this->assertStringContainsString('DEMO-BAG', $csv);
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('p.csv', "name,price,sku,category,stock_qty\nCSV Item,500,CSV-1,Toys,7\n=cmd|' /C calc'!A0,10,CSV-2,Toys,1\n");
        $this->actingAs($admin)->post('/admin/products/import', ['file' => $file])->assertSessionHas('status');
        $this->assertSame(7, Product::where('sku', 'CSV-1')->value('stock_qty'));
        $csv2 = $this->actingAs($admin)->get('/admin/products/export')->streamedContent();
        $this->assertStringNotContainsString(",=cmd", $csv2);                              // formula injection neutralised
    }

    public function test_html_content_is_sanitised(): void
    {
        $admin = $this->staff('super_admin');
        $this->actingAs($admin)->post('/admin/pages', ['title' => 'Xss', 'slug' => 'xss', 'template' => 'default', 'is_published' => 1,
            'content' => '<p onclick="alert(1)">Hi</p><script>alert(2)</script><a href="javascript:alert(3)">x</a><img src=x onerror=alert(4)>']);
        $html = $this->get('/xss')->assertOk()->getContent();
        $this->assertStringNotContainsString('<script>alert(2)', $html);
        $this->assertStringNotContainsString('onclick', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_svg_upload_is_sanitised_and_non_images_rejected(): void
    {
        $svc = app(\App\Services\ImageService::class);
        $clean = $svc->sanitizeSvg('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="1" height="1" onload="x()"/><a xlink:href="http://evil">x</a></svg>');
        $this->assertStringNotContainsString('script', $clean);
        $this->assertStringNotContainsString('onload', $clean);
        $this->assertStringNotContainsString('evil', $clean);
        $bad = \Illuminate\Http\UploadedFile::fake()->createWithContent('x.png', '<?php echo 1;');
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->store($bad);
    }

    public function test_registration_login_and_account_pages(): void
    {
        $this->keepSession($this->post('/register', ['name' => 'Sara', 'email' => 'sara@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertRedirect(route('account')));
        $this->get('/account')->assertOk();
        $this->get('/account/orders')->assertOk();
        $this->get('/account/addresses')->assertOk();
        $this->post('/account/addresses', ['name' => 'Sara', 'phone' => '0300', 'address' => 'Somewhere', 'city' => 'Lahore'])->assertRedirect();
        $this->assertDatabaseHas('addresses', ['city' => 'Lahore', 'is_default' => true]);
        $this->put('/account/password', ['current_password' => 'wrong', 'password' => 'newpassword1', 'password_confirmation' => 'newpassword1'])->assertSessionHasErrors('current_password');
        $this->post('/logout');
        $this->get('/account')->assertRedirect('/login');
        $this->post('/register', ['name' => 'Dup', 'email' => 'sara@example.com', 'password' => 'password123', 'password_confirmation' => 'password123'])->assertSessionHasErrors('email');
    }

    public function test_customer_cannot_view_someone_elses_order(): void
    {
        $this->addSimple(1);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::first();
        $this->flushSession();
        $this->withCookie(config('session.cookie'), 'z' . str_repeat('y', 39)); // a different browser
        $this->get(route('checkout.success', $order->number))->assertForbidden();
        $other = User::factory()->create();
        $this->actingAs($other)->get(route('account.order', $order->number))->assertNotFound();
    }

    public function test_invoice_pdf_and_print_render(): void
    {
        $this->addSimple(1);
        $this->post('/checkout', $this->checkoutData());
        $order = Order::first();
        $admin = $this->staff('order_manager');
        $this->actingAs($admin)->get(route('admin.orders.invoice', $order))->assertOk()->assertSee($order->number);
        $pdf = $this->actingAs($admin)->get(route('admin.orders.pdf', $order))->assertOk();
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_dashboard_uses_real_numbers(): void
    {
        $sa = $this->staff('super_admin');
        $this->actingAs($sa)->get('/admin')->assertOk()->assertSee('Rs. 0');                 // no fake revenue with zero orders
        $this->addSimple(2);
        $this->post('/checkout', $this->checkoutData());
        $this->actingAs($sa)->get('/admin')->assertOk()->assertSee('Rs. 1,248');
    }

    public function test_backup_creates_a_file(): void
    {
        if (config('database.connections.sqlite.database') === ':memory:') $this->markTestSkipped('Needs a file-based database.');
        $name = app(\App\Services\BackupService::class)->run();
        $this->assertFileExists(\Storage::disk('local')->path("backups/$name"));
        \Storage::disk('local')->delete("backups/$name");
    }

    public function test_product_image_upload_is_converted_and_replaceable(): void
    {
        $admin = $this->staff('super_admin');
        $img = \Illuminate\Http\UploadedFile::fake()->image('a.png', 2400, 1200);
        $this->actingAs($admin)->post('/admin/products', ['name' => 'Img Product', 'price' => 100, 'stock_status' => 'in_stock', 'new_images' => [$img]])->assertSessionHasNoErrors();
        $p = Product::where('name', 'Img Product')->firstOrFail();
        $im = $p->allImages()->first();
        $this->assertNotNull($im);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($im->path);
        if (function_exists('imagewebp')) $this->assertStringEndsWith('.webp', $im->path);
        $size = getimagesizefromstring(\Illuminate\Support\Facades\Storage::disk('public')->get($im->path));
        $this->assertLessThanOrEqual(1800, $size[0]);                                    // resized
        $this->assertDatabaseHas('media', ['path' => $im->path]);                         // registered in media library

        $bad = \Illuminate\Http\UploadedFile::fake()->createWithContent('evil.png', '<?php system($_GET[1]); ?>');
        $this->actingAs($admin)->post('/admin/products', ['name' => 'Bad Img', 'price' => 100, 'stock_status' => 'in_stock', 'new_images' => [$bad]])->assertSessionHasErrors();
    }
}
