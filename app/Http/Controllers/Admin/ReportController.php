<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index(Request $r)
    {
        $from = $r->filled('from') ? \Carbon\Carbon::parse($r->from)->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $r->filled('to') ? \Carbon\Carbon::parse($r->to)->endOfDay() : now()->endOfDay();
        $valid = fn () => Order::whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled', 'refunded']);
        $revenue = (float) $valid()->sum('total');
        $cost = (float) OrderItem::whereHas('order', fn ($o) => $o->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled', 'refunded']))->selectRaw('sum(coalesce(cost_price,0) * quantity) as c')->value('c');
        $costKnown = OrderItem::whereHas('order', fn ($o) => $o->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled', 'refunded']))->whereNull('cost_price')->doesntExist();
        $byStatus = Order::whereBetween('created_at', [$from, $to])->selectRaw('status, count(*) c, sum(total) t')->groupBy('status')->get();
        $byPay = $valid()->selectRaw('payment_method, count(*) c, sum(total) t')->groupBy('payment_method')->get();
        $byCity = $valid()->selectRaw('shipping_city, count(*) c, sum(total) t')->groupBy('shipping_city')->orderByDesc('t')->limit(10)->get();
        $products = OrderItem::selectRaw('name, sum(quantity) qty, sum(line_total) revenue')->whereHas('order', fn ($o) => $o->whereBetween('created_at', [$from, $to])->whereNotIn('status', ['cancelled', 'refunded']))->groupBy('name')->orderByDesc('revenue')->limit(15)->get();
        $coupons = DB::table('coupon_usages')->join('coupons', 'coupons.id', '=', 'coupon_usages.coupon_id')->whereBetween('coupon_usages.created_at', [$from, $to])->selectRaw('coupons.code, count(*) uses, sum(discount) total')->groupBy('coupons.code')->get();
        if ($r->export === 'csv') {
            return response()->streamDownload(function () use ($valid) {
                $o = fopen('php://output', 'w'); fputcsv($o, ['Order', 'Date', 'Customer', 'Status', 'Payment', 'Subtotal', 'Discount', 'Shipping', 'Total']);
                $valid()->orderBy('id')->chunk(300, fn ($c) => $c->each(fn ($x) => fputcsv($o, [$x->number, $x->created_at->toDateTimeString(), $x->customer_name, $x->status, $x->payment_method, $x->subtotal, $x->discount_total, $x->shipping_total, $x->total])));
                fclose($o);
            }, 'turtleboss-sales-' . date('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
        }
        return view('admin.reports', compact('from', 'to', 'revenue', 'cost', 'costKnown', 'byStatus', 'byPay', 'byCity', 'products', 'coupons') + ['orders' => $valid()->count()]);
    }
}
