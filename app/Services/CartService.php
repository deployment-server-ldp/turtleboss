<?php
namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    public function __construct(private CouponService $coupons, private ShippingService $shipping) {}

    public function cart(bool $create = false): ?Cart
    {
        $userId = Auth::id();
        $sid = Session::getId();
        $cart = $userId ? Cart::where('user_id', $userId)->first() : Cart::whereNull('user_id')->where('session_id', $sid)->first();
        if (!$cart && $create) {
            $cart = Cart::create(['user_id' => $userId, 'session_id' => $userId ? null : $sid]);
        }
        return $cart;
    }

    /** Called after login: move guest cart items into the user's persistent cart. */
    public function mergeGuestCart(string $oldSessionId, int $userId): void
    {
        $guest = Cart::whereNull('user_id')->where('session_id', $oldSessionId)->first();
        if (!$guest) return;
        $mine = Cart::firstOrCreate(['user_id' => $userId]);
        foreach ($guest->items as $item) {
            $existing = $mine->items()->where('product_id', $item->product_id)->where('variant_id', $item->variant_id)->first();
            $existing ? $existing->update(['quantity' => $existing->quantity + $item->quantity]) : $item->update(['cart_id' => $mine->id]);
        }
        if (!$mine->coupon_code && $guest->coupon_code) $mine->update(['coupon_code' => $guest->coupon_code]);
        $guest->delete();
    }

    public function add(int $productId, ?int $variantId, int $qty): void
    {
        $product = Product::active()->with('variants')->findOrFail($productId);
        $variant = null;
        if ($product->variants->isNotEmpty()) {
            $variant = $product->variants->where('is_active', true)->firstWhere('id', $variantId);
            if (!$variant) throw new \InvalidArgumentException('Please select a valid option.');
        }
        $qty = max(1, $qty);
        $cart = $this->cart(true);
        $item = $cart->items()->where('product_id', $product->id)->where('variant_id', $variant?->id)->first();
        $newQty = ($item?->quantity ?? 0) + $qty;
        $this->assertStock($product, $variant, $newQty);
        $item ? $item->update(['quantity' => $newQty]) : $cart->items()->create(['product_id' => $product->id, 'variant_id' => $variant?->id, 'quantity' => $qty]);
    }

    public function setQuantity(int $itemId, int $qty): void
    {
        $cart = $this->cart();
        $item = $cart?->items()->where('cart_items.id', $itemId)->first();
        if (!$item) return;
        if ($qty < 1) { $item->delete(); return; }
        $this->assertStock($item->product, $item->variant, $qty);
        $item->update(['quantity' => $qty]);
    }

    public function changeVariant(int $itemId, int $variantId): void
    {
        $cart = $this->cart();
        $item = $cart?->items()->where('cart_items.id', $itemId)->first();
        if (!$item) return;
        $variant = $item->product->variants()->where('is_active', true)->findOrFail($variantId);
        $this->assertStock($item->product, $variant, $item->quantity);
        $dupe = $cart->items()->where('product_id', $item->product_id)->where('variant_id', $variant->id)->where('cart_items.id', '!=', $item->id)->first();
        if ($dupe) { $dupe->update(['quantity' => $dupe->quantity + $item->quantity]); $item->delete(); }
        else $item->update(['variant_id' => $variant->id]);
    }

    public function remove(int $itemId): void
    {
        $this->cart()?->items()->where('cart_items.id', $itemId)->get()->each->delete();
    }

    public function clear(): void
    {
        $cart = $this->cart();
        if ($cart) { $cart->items()->get()->each->delete(); $cart->update(['coupon_code' => null]); }
    }

    public function assertStock(Product $product, ?ProductVariant $variant, int $qty): void
    {
        if (!$product->is_active) throw new \InvalidArgumentException('This product is no longer available.');
        if ($variant) {
            if ($product->manage_stock !== false && $product->stock_status !== 'backorder' && $variant->stock_qty < $qty) {
                throw new \InvalidArgumentException($variant->stock_qty > 0 ? "Only {$variant->stock_qty} left in stock for this option." : 'This option is out of stock.');
            }
            return;
        }
        if ($product->stock_status === 'out_of_stock') throw new \InvalidArgumentException('This product is out of stock.');
        if ($product->manage_stock && $product->stock_status !== 'backorder' && $product->stock_qty < $qty) {
            throw new \InvalidArgumentException($product->stock_qty > 0 ? "Only {$product->stock_qty} left in stock." : 'This product is out of stock.');
        }
    }

    /** Normalised lines with live prices straight from the database. */
    public function lines(?Cart $cart = null): Collection
    {
        $cart ??= $this->cart();
        if (!$cart) return collect();
        return $cart->items()->get()->filter(fn ($i) => $i->product && $i->product->is_active)->map(function (CartItem $i) {
            $p = $i->product;
            $v = $i->variant;
            $unit = $v ? $v->final_price_for($p) : $p->final_price;
            $regular = $v ? $v->regular_price_for($p) : $p->regular_price;
            $img = $v?->images->first()?->path ?? $p->images->first()?->path;
            $stock = $v ? $v->stock_qty : ($p->manage_stock ? $p->stock_qty : 9999);
            if ($p->stock_status === 'backorder') $stock = 9999;
            if (!$v && $p->stock_status === 'out_of_stock') $stock = 0;
            return [
                'id' => $i->id, 'item' => $i, 'product' => $p, 'variant' => $v,
                'name' => $p->name, 'label' => $v?->label, 'sku' => $v?->sku ?: $p->sku,
                'image' => img($img), 'url' => $p->url, 'qty' => $i->quantity,
                'unit' => (float) $unit, 'regular' => (float) $regular, 'line_total' => round($unit * $i->quantity, 2),
                'stock' => $stock, 'cost' => $p->cost_price,
                'options' => $p->variants->where('is_active', true)->map(fn ($x) => ['id' => $x->id, 'label' => $x->label])->values(),
            ];
        })->values();
    }

    /** Full price breakdown. Coupon is re-validated every time. */
    public function totals(?string $city = null, ?Cart $cart = null, ?string $email = null): array
    {
        $cart ??= $this->cart();
        $lines = $this->lines($cart);
        $subtotal = round((float) $lines->sum('line_total'), 2);
        $discount = 0.0; $freeShip = false; $couponError = null; $coupon = null;
        if ($cart && $cart->coupon_code && $lines->isNotEmpty()) {
            try {
                $r = $this->coupons->evaluate($cart->coupon_code, $lines, $subtotal, Auth::id(), $email ?? Auth::user()?->email);
                $coupon = $r['coupon']; $discount = $r['discount']; $freeShip = $r['free_shipping'];
            } catch (\InvalidArgumentException $e) {
                $couponError = $e->getMessage();
                $cart->update(['coupon_code' => null]);
            }
        }
        $after = max(0, $subtotal - $discount);
        $ship = $lines->isEmpty() ? ['amount' => 0.0, 'label' => '', 'free' => false] : $this->shipping->quote($after, $city, $freeShip);
        return [
            'lines' => $lines, 'count' => (int) $lines->sum('qty'), 'subtotal' => $subtotal,
            'discount' => $discount, 'coupon' => $coupon, 'coupon_error' => $couponError,
            'shipping' => $ship['amount'], 'shipping_label' => $ship['label'], 'shipping_free' => $ship['free'],
            'total' => round($after + $ship['amount'], 2),
        ];
    }

    public function applyCoupon(string $code, ?string $city = null): void
    {
        $cart = $this->cart();
        if (!$cart) throw new \InvalidArgumentException('Your cart is empty.');
        $lines = $this->lines($cart);
        $subtotal = (float) $lines->sum('line_total');
        $this->coupons->evaluate($code, $lines, $subtotal, Auth::id(), Auth::user()?->email); // throws
        $cart->update(['coupon_code' => trim($code)]);
    }

    public function removeCoupon(): void { $this->cart()?->update(['coupon_code' => null]); }

    /** JSON shape consumed by the storefront cart drawer. */
    public function payload(?string $city = null): array
    {
        $t = $this->totals($city);
        return [
            'count' => $t['count'], 'subtotal' => $t['subtotal'], 'discount' => $t['discount'], 'shipping' => $t['shipping'],
            'shipping_label' => $t['shipping_label'], 'total' => $t['total'],
            'coupon' => $t['coupon']?->code, 'coupon_error' => $t['coupon_error'],
            'free_threshold' => (float) setting('shipping_free_threshold', 0),
            'items' => $t['lines']->map(fn ($l) => [
                'id' => $l['id'], 'name' => $l['name'], 'label' => $l['label'], 'image' => $l['image'], 'url' => $l['url'],
                'qty' => $l['qty'], 'unit' => $l['unit'], 'regular' => $l['regular'], 'line_total' => $l['line_total'], 'stock' => $l['stock'], 'options' => $l['options'],
            ])->values(),
        ];
    }
}
