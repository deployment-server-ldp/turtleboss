<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\ShippingService;
use App\Support\Seo;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private OrderService $orders) {}

    public static function methods(): array
    {
        return array_filter([
            'cod' => setting('pay_cod_enabled', '1') === '1' ? ['label' => 'Cash on Delivery', 'desc' => 'Pay in cash when your order arrives.'] : null,
            'bank_transfer' => setting('pay_bank_enabled', '1') === '1' ? ['label' => 'Bank Transfer', 'desc' => 'Transfer to our bank account and share the receipt.'] : null,
            'gateway' => setting('pay_gateway_enabled') === '1' && setting('gateway_key') ? ['label' => 'Online Payment', 'desc' => 'Pay securely online.'] : null,
        ]);
    }

    public function show()
    {
        $user = auth()->user();
        $totals = $this->cart->totals($user?->customer?->addresses()->where('is_default', true)->value('city'));
        if ($totals['lines']->isEmpty()) return redirect()->route('cart')->with('status', 'Your cart is empty.');
        $addresses = $user?->customer?->addresses ?? collect();
        return view('checkout.show', [
            'cities' => ShippingService::cities(), 'provinces' => ShippingService::provinces(), 'methods' => self::methods(),
            'addresses' => $addresses, 'user' => $user, 'seo' => Seo::make(['title' => 'Checkout', 'noindex' => true]),
        ]);
    }

    public function place(Request $r)
    {
        $methods = array_keys(self::methods());
        $d = $r->validate([
            'name' => 'required|string|max:100', 'email' => 'required|email|max:120', 'phone' => ['required', 'regex:/^[0-9+\-\s()]{10,18}$/'],
            'address' => 'required|string|max:255', 'city' => 'required|string|max:80', 'province' => 'nullable|string|max:80',
            'postal_code' => 'nullable|string|max:16', 'notes' => 'nullable|string|max:1000',
            'payment_method' => 'required|in:' . implode(',', $methods ?: ['cod']),
            'save_address' => 'nullable|boolean',
        ], ['phone.regex' => 'Please enter a valid phone number.']);

        try { $order = $this->orders->place($d); }
        catch (\InvalidArgumentException $e) { return back()->withInput()->withErrors(['cart' => $e->getMessage()]); }

        if (auth()->check() && $r->boolean('save_address')) {
            $c = auth()->user()->customer()->firstOrCreate([], ['name' => auth()->user()->name, 'email' => auth()->user()->email]);
            $c->addresses()->firstOrCreate(['address' => $d['address'], 'city' => $d['city']], ['name' => $d['name'], 'phone' => $d['phone'], 'province' => $d['province'] ?? null, 'postal_code' => $d['postal_code'] ?? null, 'is_default' => !$c->addresses()->exists()]);
        }
        session(['last_order' => $order->number]);
        return redirect()->route('checkout.success', $order->number);
    }

    public function success(string $number)
    {
        $order = Order::with('items')->where('number', $number)->firstOrFail();
        // Only the buyer (same session or owning user) can view the confirmation.
        abort_unless(session('last_order') === $number || (auth()->id() && $order->user_id === auth()->id()), 403);
        return view('checkout.success', ['order' => $order, 'seo' => Seo::make(['title' => 'Order Confirmed', 'noindex' => true])]);
    }
}
