<?php
namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(private CartService $carts, private InventoryService $inventory, private NotificationService $notify) {}

    /** Create an order from the current cart. Re-validates stock, prices, coupon and shipping server-side. */
    public function place(array $data): Order
    {
        $order = DB::transaction(function () use ($data) {
            $cart = $this->carts->cart();
            $t = $this->carts->totals($data['city'], $cart, $data['email']);
            if ($t['lines']->isEmpty()) throw new \InvalidArgumentException('Your cart is empty.');

            // lock + verify stock
            foreach ($t['lines'] as $l) {
                $this->carts->assertStock(Product::lockForUpdate()->find($l['product']->id), $l['variant'] ? $l['variant']->fresh() : null, $l['qty']);
            }

            $user = Auth::user();
            $customer = Customer::firstOrCreate(
                $user ? ['user_id' => $user->id] : ['email' => $data['email'], 'user_id' => null],
                ['name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone']]
            );
            if (!$user && $customer->name !== $data['name']) $customer->update(['name' => $data['name'], 'phone' => $data['phone']]);

            $order = Order::create([
                'number' => $this->nextNumber(), 'user_id' => $user?->id, 'customer_id' => $customer->id, 'status' => 'pending',
                'payment_method' => $data['payment_method'], 'payment_status' => 'unpaid',
                'customer_name' => $data['name'], 'customer_email' => $data['email'], 'customer_phone' => $data['phone'],
                'shipping_address' => $data['address'], 'shipping_city' => $data['city'], 'shipping_province' => $data['province'] ?? null,
                'shipping_postal_code' => $data['postal_code'] ?? null, 'customer_notes' => $data['notes'] ?? null,
                'subtotal' => $t['subtotal'], 'discount_total' => $t['discount'], 'shipping_total' => $t['shipping'], 'total' => $t['total'],
                'coupon_code' => $t['coupon']?->code,
            ]);

            foreach ($t['lines'] as $l) {
                $order->items()->create([
                    'product_id' => $l['product']->id, 'variant_id' => $l['variant']?->id, 'name' => $l['name'], 'variant_label' => $l['label'],
                    'sku' => $l['sku'], 'image' => $l['product']->images->first()?->path, 'unit_price' => $l['unit'], 'cost_price' => $l['cost'],
                    'quantity' => $l['qty'], 'line_total' => $l['line_total'],
                ]);
                $this->inventory->adjust($l['product'], $l['variant'], -$l['qty'], 'order', "Order {$order->number}");
                $l['product']->increment('sales_count', $l['qty']);
            }
            Payment::create(['order_id' => $order->id, 'method' => $data['payment_method'], 'status' => 'pending', 'amount' => $order->total]);
            OrderNote::create(['order_id' => $order->id, 'note' => 'Order placed by customer.', 'is_status_change' => true]);

            if ($t['coupon']) {
                CouponUsage::create(['coupon_id' => $t['coupon']->id, 'order_id' => $order->id, 'user_id' => $user?->id, 'email' => $data['email'], 'discount' => $t['discount']]);
                Coupon::whereKey($t['coupon']->id)->increment('used_count');
            }
            $this->carts->clear();
            return $order;
        });
        $this->notify->orderPlaced($order);
        return $order;
    }

    private function nextNumber(): string
    {
        do { $n = 'TB-' . now()->format('ymd') . '-' . random_int(1000, 9999); } while (Order::where('number', $n)->exists());
        return $n;
    }

    /** Change status, restock on cancel, log history and notify. */
    public function setStatus(Order $order, string $status, ?string $note = null): void
    {
        if (!array_key_exists($status, Order::STATUSES) || $order->status === $status) return;
        DB::transaction(function () use ($order, $status, $note) {
            $old = $order->status;
            $restockStates = ['cancelled'];
            if (in_array($status, $restockStates) && !in_array($old, $restockStates)) {
                foreach ($order->items as $i) if ($i->product) $this->inventory->adjust($i->product, $i->variant_id ? \App\Models\ProductVariant::find($i->variant_id) : null, $i->quantity, 'cancel', "Order {$order->number} cancelled");
            }
            if (in_array($old, $restockStates) && !in_array($status, $restockStates)) {
                foreach ($order->items as $i) if ($i->product) $this->inventory->adjust($i->product, $i->variant_id ? \App\Models\ProductVariant::find($i->variant_id) : null, -$i->quantity, 'order', "Order {$order->number} reopened");
            }
            $update = ['status' => $status];
            if ($status === 'completed' && $order->payment_method === 'cod') $update['payment_status'] = 'paid';
            $order->update($update);
            if ($status === 'completed') $order->payments()->where('status', 'pending')->update(['status' => 'paid']);
            OrderNote::create(['order_id' => $order->id, 'user_id' => Auth::id(), 'is_status_change' => true,
                'note' => "Status changed from " . (Order::STATUSES[$old] ?? $old) . " to " . Order::STATUSES[$status] . ($note ? ": $note" : '')]);
        });
        $this->notify->statusChanged($order->fresh());
    }
}
