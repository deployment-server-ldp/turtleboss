<?php
namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * DEMO DATA ONLY – for development/QA. Every product is flagged is_demo=true and shows a DEMO badge in admin.
 * Remove with:  php artisan turtleboss:remove-demo   (also delete coupon DEMO10 in admin if you wish)
 */
class DemoSeeder extends Seeder
{
    private function tile(string $label, string $bg, string $fg = '#07182F'): string
    {
        $path = 'demo/' . Str::slug($label) . '-' . substr(md5($bg), 0, 4) . '.svg';
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 800"><rect width="800" height="800" fill="' . $bg . '"/><text x="400" y="380" text-anchor="middle" font-family="Arial,sans-serif" font-size="46" font-weight="700" fill="' . $fg . '">' . e($label) . '</text><text x="400" y="440" text-anchor="middle" font-family="Arial,sans-serif" font-size="26" fill="' . $fg . '" opacity=".6">DEMO IMAGE</text></svg>';
        Storage::disk('public')->put($path, $svg);
        return $path;
    }

    public function run(): void
    {
        $cat = fn ($slug) => Category::where('slug', $slug)->first();
        $color = Attribute::where('slug', 'color')->first()->values->keyBy('value');
        $size = Attribute::where('slug', 'size')->first()->values->keyBy('value');

        $make = function (array $d, string $catSlug, array $imgs = [['#EEF3FA']]) use ($cat) {
            $c = $cat($catSlug);
            $p = Product::updateOrCreate(['slug' => Str::slug($d['name'])], $d + ['category_id' => $c->id, 'is_demo' => true, 'is_active' => true, 'manage_stock' => true, 'stock_status' => 'in_stock', 'short_description' => 'Demo product for testing the store. Delete from Admin → Products.', 'description' => '<p>This is a <b>demo product</b> used to test the store. Remove all demo products with <code>php artisan turtleboss:remove-demo</code>.</p>']);
            $p->categories()->sync([$c->id]);
            if (!$p->allImages()->exists()) foreach ($imgs as $i => [$bg]) $p->allImages()->create(['path' => $this->tile($d['name'] . ' ' . ($i + 1), $bg), 'alt' => $d['name'], 'sort_order' => $i]);
            return $p;
        };

        // Variant product (matches the Premium Storage Bag example in the brief)
        $bag = $make(['name' => 'Demo Premium Storage Bag', 'sku' => 'DEMO-BAG', 'price' => 1800, 'sale_price' => 1500, 'stock_qty' => 0, 'is_featured' => true, 'is_new' => true, 'is_bestseller' => true], 'organizers', [['#FFF4C2'], ['#DCE8FF'], ['#E7E7E7'], ['#F6E3E3']]);
        if (!$bag->variants()->exists()) {
            $n = 0;
            foreach (['Yellow' => 'Yellow', 'Blue' => 'Blue', 'Black' => 'Black'] as $c) {
                foreach (['Small' => 0, 'Medium' => 200, 'Large' => 400] as $s => $extra) {
                    $v = $bag->variants()->create(['sku' => 'DEMO-BAG-' . strtoupper(substr($c, 0, 3)) . '-' . strtoupper(substr($s, 0, 1)), 'price' => 1800 + $extra, 'sale_price' => $s === 'Large' ? null : 1500 + $extra, 'stock_qty' => ($c === 'Black' && $s === 'Large') ? 0 : 12, 'sort_order' => $n++]);
                    $v->values()->sync([$color[$c]->id, $size[$s]->id]);
                }
                $bg = ['Yellow' => '#F9E27D', 'Blue' => '#9DB9F5', 'Black' => '#3a3a3a'][$c];
                $bag->variants()->where('sku', 'like', 'DEMO-BAG-' . strtoupper(substr($c, 0, 3)) . '-M')->first()->images()->create(['product_id' => $bag->id, 'path' => $this->tile("Storage Bag $c", $bg, $c === 'Black' ? '#fff' : '#07182F'), 'alt' => "Storage bag $c"]);
            }
            $bag->specs()->createMany([['name' => 'Material', 'value' => 'Non-woven fabric'], ['name' => 'Closure', 'value' => 'Zip'], ['name' => 'Washable', 'value' => 'Yes']]);
        }

        $make(['name' => 'Demo Kitchen Organizer Rack', 'sku' => 'DEMO-KIT-1', 'price' => 2400, 'stock_qty' => 30, 'is_featured' => true, 'is_trending' => true, 'brand' => 'Turtle Boss'], 'kitchen-accessories');
        $make(['name' => 'Demo Microfiber Cleaning Cloth Set', 'sku' => 'DEMO-CLN-1', 'price' => 650, 'sale_price' => 499, 'stock_qty' => 80, 'is_bestseller' => true, 'is_featured' => true], 'cleaning-products');
        $make(['name' => 'Demo Bathroom Shelf', 'sku' => 'DEMO-HH-1', 'price' => 3200, 'stock_qty' => 4, 'is_new' => true, 'is_trending' => true], 'household');
        $make(['name' => 'Demo Personal Care Kit', 'sku' => 'DEMO-PC-1', 'price' => 1250, 'stock_qty' => 25, 'is_new' => true, 'is_bestseller' => true], 'personal-care');
        $make(['name' => 'Demo Building Blocks Toy', 'sku' => 'DEMO-TOY-1', 'price' => 1999, 'sale_price' => 1599, 'stock_qty' => 15, 'is_featured' => true, 'is_new' => true], 'toys');
        $make(['name' => 'Demo Custom Printed Mug', 'sku' => 'DEMO-CUS-1', 'price' => 899, 'stock_qty' => 0, 'stock_status' => 'out_of_stock', 'is_trending' => true], 'customize-products');
        $make(['name' => 'Demo Drawer Divider Set', 'sku' => 'DEMO-ORG-2', 'price' => 1100, 'stock_qty' => 40, 'is_bestseller' => true], 'organizers');

        Coupon::firstOrCreate(['code' => 'DEMO10'], ['description' => 'DEMO: 10% off, min Rs. 1000', 'type' => 'percent', 'amount' => 10, 'min_spend' => 1000, 'max_discount' => 500]);
        $this->command?->info('Demo products + coupon DEMO10 created. Remove with: php artisan turtleboss:remove-demo');
    }
}
