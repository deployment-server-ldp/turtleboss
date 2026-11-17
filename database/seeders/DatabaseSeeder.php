<?php
namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\HomepageSection;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Setting;
use App\Models\ShippingRule;
use App\Models\Slider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/** Production-safe baseline: roles, admin, categories, menus, pages, settings, homepage layout. No fake products. */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->roles();
        $this->admin();
        $this->categories();
        $this->attributes();
        $this->pages();
        $this->menus();
        $this->settings();
        $this->homepage();
        if (!ShippingRule::exists()) {
            ShippingRule::create(['name' => 'Standard shipping', 'rate' => 250, 'free_over' => 3000, 'priority' => 10]);
        }
    }

    private function roles(): void
    {
        $cfg = config('permissions');
        $perms = [];
        foreach ($cfg['modules'] as $slug => $name) $perms[$slug] = Permission::updateOrCreate(['slug' => $slug], ['name' => $name]);
        foreach ($cfg['roles'] as $slug => $r) {
            $role = Role::updateOrCreate(['slug' => $slug], ['name' => $r['name'], 'description' => $r['description']]);
            $mods = $r['modules'] === '*' ? array_keys($perms) : $r['modules'];
            $role->permissions()->sync(collect($mods)->map(fn ($m) => $perms[$m]->id)->all());
        }
    }

    private function admin(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@turtleboss.pk');
        if (User::where('email', $email)->exists()) return;
        $pw = env('ADMIN_PASSWORD') ?: Str::password(16);
        User::create(['name' => 'Super Admin', 'email' => $email, 'password' => $pw, 'role_id' => Role::where('slug', 'super_admin')->value('id'), 'email_verified_at' => now()]);
        $this->command?->warn("Super Admin created: $email / $pw  (change it after first login)");
    }

    private function categories(): void
    {
        foreach (['Cleaning Products', 'Customize Products', 'Kitchen Accessories', 'Organizers', 'Household', 'Personal Care', 'Toys'] as $i => $name) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $i + 1, 'description' => "Shop Turtle Boss $name."]);
        }
    }

    private function attributes(): void
    {
        $defs = [
            'Color' => ['color', ['Black' => '#111111', 'White' => '#FFFFFF', 'Blue' => '#2F5BEA', 'Yellow' => '#F2C230', 'Red' => '#D93B3B', 'Green' => '#2E9D5B']],
            'Size' => ['select', ['Small' => null, 'Medium' => null, 'Large' => null, 'XL' => null]],
            'Material' => ['select', ['Plastic' => null, 'Stainless Steel' => null, 'Cotton' => null]],
        ];
        $o = 1;
        foreach ($defs as $name => [$type, $vals]) {
            $a = Attribute::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'type' => $type, 'sort_order' => $o++]);
            $n = 1;
            foreach ($vals as $v => $hex) AttributeValue::firstOrCreate(['attribute_id' => $a->id, 'value' => $v], ['color_hex' => $hex, 'sort_order' => $n++]);
        }
    }

    private function pages(): void
    {
        $pages = [
            ['About Us', 'about-us', 'default', "<h2>About Turtle Boss</h2><p><strong>Everything Is Possible.</strong></p><p>Turtle Boss brings practical, good-quality household, kitchen, cleaning, personal care and lifestyle products to homes across Pakistan. Edit this page from Admin → Pages.</p>"],
            ['FAQ', 'faq', 'faq', "<h3>How long does delivery take?</h3><p>Delivery times depend on your city. Edit this answer in Admin → Pages.</p><h3>What payment methods do you accept?</h3><p>Cash on Delivery and Bank Transfer.</p><h3>Can I return a product?</h3><p>Please read our Return &amp; Refund Policy.</p>"],
            ['Contact Us', 'contact-us', 'contact', "<p>We'd love to hear from you. Send us a message using the form and our team will get back to you.</p>"],
            ['Privacy Policy', 'privacy-policy', 'default', '<p>Add your privacy policy here from Admin → Pages.</p>'],
            ['Terms & Conditions', 'terms-and-conditions', 'default', '<p>Add your terms and conditions here from Admin → Pages.</p>'],
            ['Return & Refund Policy', 'return-refund-policy', 'default', '<p>Add your return and refund policy here from Admin → Pages.</p>'],
            ['Shipping Policy', 'shipping-policy', 'default', '<p>Add your shipping policy here from Admin → Pages.</p>'],
        ];
        foreach ($pages as [$t, $s, $tpl, $c]) Page::firstOrCreate(['slug' => $s], ['title' => $t, 'template' => $tpl, 'content' => $c]);
    }

    private function menus(): void
    {
        $header = Menu::firstOrCreate(['location' => 'header'], ['name' => 'Header Menu']);
        if (!$header->items()->exists()) {
            $header->items()->create(['label' => 'Home', 'url' => '/', 'sort_order' => 1]);
            $header->items()->create(['label' => 'Shop', 'url' => '/shop', 'sort_order' => 2]);
            $header->items()->create(['label' => 'Our Categories', 'url' => '/shop', 'type' => 'mega_categories', 'sort_order' => 3]);
            $header->items()->create(['label' => 'About Us', 'url' => '/about-us', 'sort_order' => 4]);
            $header->items()->create(['label' => 'FAQ', 'url' => '/faq', 'sort_order' => 5]);
            $header->items()->create(['label' => 'Contact Us', 'url' => '/contact-us', 'sort_order' => 6]);
        }
        $fh = Menu::firstOrCreate(['location' => 'footer_help'], ['name' => 'Footer – Help']);
        if (!$fh->items()->exists()) {
            foreach ([['About Us', '/about-us'], ['FAQ', '/faq'], ['Contact Us', '/contact-us'], ['Shipping Policy', '/shipping-policy'], ['Return & Refund Policy', '/return-refund-policy'], ['Privacy Policy', '/privacy-policy'], ['Terms & Conditions', '/terms-and-conditions']] as $i => [$l, $u]) {
                $fh->items()->create(['label' => $l, 'url' => $u, 'sort_order' => $i + 1]);
            }
        }
        $fs = Menu::firstOrCreate(['location' => 'footer_shop'], ['name' => 'Footer – Shop']);
        if (!$fs->items()->exists()) {
            $fs->items()->create(['label' => 'All Products', 'url' => '/shop', 'sort_order' => 1]);
            foreach (Category::orderBy('sort_order')->get() as $i => $c) $fs->items()->create(['label' => $c->name, 'url' => '/category/' . $c->slug, 'sort_order' => $i + 2]);
        }
    }

    private function settings(): void
    {
        $defaults = [
            'site_name' => 'Turtle Boss', 'tagline' => 'Everything Is Possible.', 'currency_symbol' => 'Rs.',
            'seo_title' => 'Turtle Boss – Everything Is Possible', 'seo_description' => 'Shop cleaning products, kitchen accessories, organizers, household, personal care and toys at Turtle Boss Pakistan.',
            'contact_email' => 'info@turtleboss.pk', 'contact_phone' => '', 'contact_address' => '', 'whatsapp_number' => '',
            'announcement_enabled' => '1', 'announcement_messages' => "Free shipping on orders over Rs. 3,000\nCash on Delivery available across Pakistan",
            'shipping_flat_rate' => '250', 'shipping_free_threshold' => '3000',
            'pay_cod_enabled' => '1', 'pay_bank_enabled' => '1', 'pay_gateway_enabled' => '0',
            'bank_details' => "Bank: (your bank)\nAccount title: Turtle Boss\nAccount no: (your account)\nIBAN: (your IBAN)",
            'robots_txt' => "User-agent: *\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /account\n",
            'slider_interval' => '5000', 'email_enabled' => '1', 'low_stock_default' => '5',
            'social_facebook' => '', 'social_instagram' => '', 'social_tiktok' => '', 'ga_id' => '', 'gsc_verification' => '',
            'shipping_info' => 'Delivered across Pakistan in 3–7 working days.', 'payment_info' => 'Cash on Delivery · Bank Transfer',
        ];
        foreach ($defaults as $k => $v) if (Setting::where('key', $k)->doesntExist()) Setting::put($k, $v);
    }

    private function homepage(): void
    {
        if (HomepageSection::exists()) return;
        $sections = [
            ['categories', 'Shop by Categories', 'Find what you need', []],
            ['products', 'Featured Products', 'Handpicked for you', ['source' => 'featured', 'limit' => 8]],
            ['products', 'Best Sellers', 'Loved by our customers', ['source' => 'bestseller', 'limit' => 8]],
            ['banners', null, null, ['items' => []]],
            ['products', 'New Arrivals', 'Fresh in store', ['source' => 'new', 'limit' => 8]],
            ['products', 'Trending Now', 'What everyone is buying', ['source' => 'trending', 'limit' => 8]],
            ['why_choose', 'Why Choose Turtle Boss', null, ['items' => [
                ['icon' => 'badge', 'title' => 'Quality Products', 'text' => 'Carefully selected products that last.'],
                ['icon' => 'smile', 'title' => 'Customer Satisfaction', 'text' => 'Friendly support whenever you need it.'],
                ['icon' => 'lock', 'title' => 'Secure Payments', 'text' => 'Safe and flexible payment options.'],
                ['icon' => 'truck', 'title' => 'Reliable Delivery', 'text' => 'Fast, tracked delivery across Pakistan.'],
            ]]],
            ['testimonials', 'What Customers Say', 'Verified reviews from real orders', ['limit' => 6]],
            ['newsletter', 'Join the Turtle Boss Newsletter', 'Be first to hear about new products and offers.', []],
        ];
        foreach ($sections as $i => [$type, $title, $sub, $cfg]) HomepageSection::create(['type' => $type, 'title' => $title, 'subtitle' => $sub, 'config' => $cfg, 'sort_order' => $i + 1]);
    }
}
