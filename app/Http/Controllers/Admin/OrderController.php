<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\Refund;
use App\Services\InventoryService;
use App\Services\NotificationService;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        $q = Order::query()->withCount('items');
        if ($t = trim((string) $r->q)) $q->where(fn ($w) => $w->where('number', 'like', "%$t%")->orWhere('customer_name', 'like', "%$t%")->orWhere('customer_email', 'like', "%$t%")->orWhere('customer_phone', 'like', "%$t%"));
        if ($r->filled('status')) $q->where('status', $r->status);
        if ($r->filled('payment')) $q->where('payment_method', $r->payment);
        if ($r->filled('from')) $q->whereDate('created_at', '>=', $r->from);
        if ($r->filled('to')) $q->whereDate('created_at', '<=', $r->to);
        $counts = Order::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');
        return view('admin.orders.index', ['orders' => $q->latest()->paginate(20)->withQueryString(), 'counts' => $counts]);
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'notes.user', 'payments', 'refunds.user', 'customer', 'shipments');
        return view('admin.orders.show', ['order' => $order, 'notifications' => \App\Models\Notification::where('notifiable_type', Order::class)->where('notifiable_id', $order->id)->latest()->get()]);
    }

    public function status(Request $r, Order $order, OrderService $svc)
    {
        $d = $r->validate(['status' => 'required|in:' . implode(',', array_keys(Order::STATUSES)), 'note' => 'nullable|max:500']);
        $svc->setStatus($order, $d['status'], $d['note'] ?? null);
        return back()->with('status', 'Order status updated.');
    }

    public function tracking(Request $r, Order $order, NotificationService $n)
    {
        $d = $r->validate(['tracking_number' => 'nullable|max:100', 'courier' => 'nullable|max:80']);
        $order->update($d);
        if (!empty($d['tracking_number'])) {
            $order->shipments()->updateOrCreate(['order_id' => $order->id], ['courier' => $d['courier'] ?? null, 'tracking_number' => $d['tracking_number'], 'shipped_at' => now()]);
            OrderNote::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'note' => 'Tracking added: ' . $d['tracking_number'] . ($d['courier'] ? " ({$d['courier']})" : ''), 'is_status_change' => true]);
        }
        return back()->with('status', 'Tracking details saved.');
    }

    public function note(Request $r, Order $order)
    {
        $d = $r->validate(['note' => 'required|max:1000']);
        OrderNote::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'note' => $d['note']]);
        return back()->with('status', 'Note added.');
    }

    public function payment(Request $r, Order $order)
    {
        $d = $r->validate(['payment_status' => 'required|in:unpaid,paid']);
        $order->update($d);
        $order->payments()->update(['status' => $d['payment_status'] === 'paid' ? 'paid' : 'pending']);
        OrderNote::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'is_status_change' => true, 'note' => 'Payment marked ' . $d['payment_status']]);
        return back()->with('status', 'Payment status updated.');
    }

    public function refund(Request $r, Order $order, InventoryService $inv, OrderService $svc)
    {
        $max = round($order->total - $order->refunded_total, 2);
        $d = $r->validate(['amount' => "required|numeric|min:1|max:$max", 'reason' => 'nullable|max:255', 'restock' => 'nullable|boolean']);
        Refund::create(['order_id' => $order->id, 'amount' => $d['amount'], 'reason' => $d['reason'] ?? null, 'restock' => $r->boolean('restock'), 'user_id' => auth()->id()]);
        $refunded = $order->refunded_total + $d['amount'];
        $order->update(['refunded_total' => $refunded, 'payment_status' => $refunded >= $order->total ? 'refunded' : 'partially_refunded']);
        if ($r->boolean('restock') && $order->status !== 'cancelled') {
            foreach ($order->items as $i) if ($i->product) $inv->adjust($i->product, $i->variant_id ? \App\Models\ProductVariant::find($i->variant_id) : null, $i->quantity, 'refund', "Refund on {$order->number}");
        }
        OrderNote::create(['order_id' => $order->id, 'user_id' => auth()->id(), 'is_status_change' => true, 'note' => 'Refund of ' . money($d['amount']) . ($d['reason'] ? " – {$d['reason']}" : '')]);
        if ($refunded >= $order->total && $order->status !== 'refunded') $svc->setStatus($order->fresh(), 'refunded');
        return back()->with('status', 'Refund recorded. (Money is not moved automatically – refund the customer via your bank/gateway.)');
    }

    public function invoice(Order $order) { return view('admin.orders.invoice', ['order' => $order->load('items'), 'print' => true]); }
    public function pdf(Order $order)
    {
        return Pdf::loadView('admin.orders.invoice', ['order' => $order->load('items'), 'print' => false])->setPaper('a4')->download("invoice-{$order->number}.pdf");
    }
}
