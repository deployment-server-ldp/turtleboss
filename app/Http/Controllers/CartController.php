<?php
namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        return view('cart.index', ['seo' => \App\Support\Seo::make(['title' => 'Shopping Cart', 'noindex' => true])]);
    }

    public function data() { return response()->json($this->cart->payload()); }

    private function respond(callable $fn, string $ok = '')
    {
        try { $fn(); } catch (\InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage(), 'cart' => $this->cart->payload()], 422);
        }
        return response()->json(['ok' => true, 'message' => $ok, 'cart' => $this->cart->payload()]);
    }

    public function add(Request $r)
    {
        $d = $r->validate(['product_id' => 'required|integer', 'variant_id' => 'nullable|integer', 'quantity' => 'nullable|integer|min:1|max:99']);
        return $this->respond(fn () => $this->cart->add($d['product_id'], $d['variant_id'] ?? null, $d['quantity'] ?? 1), 'Added to cart');
    }
    public function update(Request $r)
    {
        $d = $r->validate(['item_id' => 'required|integer', 'quantity' => 'required|integer|min:0|max:99']);
        return $this->respond(fn () => $this->cart->setQuantity($d['item_id'], $d['quantity']));
    }
    public function variant(Request $r)
    {
        $d = $r->validate(['item_id' => 'required|integer', 'variant_id' => 'required|integer']);
        return $this->respond(fn () => $this->cart->changeVariant($d['item_id'], $d['variant_id']), 'Option updated');
    }
    public function remove(Request $r)
    {
        $d = $r->validate(['item_id' => 'required|integer']);
        return $this->respond(fn () => $this->cart->remove($d['item_id']), 'Removed');
    }
    public function coupon(Request $r)
    {
        $d = $r->validate(['code' => 'required|string|max:60', 'city' => 'nullable|string|max:80']);
        return $this->respond(fn () => $this->cart->applyCoupon($d['code'], $d['city'] ?? null), 'Coupon applied');
    }
    public function removeCoupon()
    {
        return $this->respond(fn () => $this->cart->removeCoupon(), 'Coupon removed');
    }
    /** Checkout summary with a city-specific shipping quote. */
    public function quote(Request $r) { return response()->json($this->cart->payload($r->query('city'))); }
}
