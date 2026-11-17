<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $valid = fn () => Order::whereNotIn('status', ['cancelled', 'refunded']);
        $from = now()->subDays(29)->startOfDay();
        $byDay = $valid()->where('created_at', '>=', $from)->selectRaw('date(created_at) as d, count(*) as orders, sum(total) as revenue')->groupBy('d')->get()->keyBy('d');
        $series = collect(range(0, 29))->map(function ($i) use ($from, $byDay) {
            $d = $from->copy()->addDays($i)->toDateString();
            return ['date' => $d, 'label' => $from->copy()->addDays($i)->format('d M'), 'orders' => (int) ($byDay[$d]->orders ?? 0), 'revenue' => (float) ($byDay[$d]->revenue ?? 0)];
        });

        $top = OrderItem::selectRaw('product_id, name, sum(quantity) as qty, sum(line_total) as revenue')->whereHas('order', fn ($o) => $o->whereNotIn('status', ['cancelled', 'refunded']))
            ->groupBy('product_id', 'name')->orderByDesc('qty')->limit(5)->get();
        $topCats = DB::table('order_items as oi')->join('orders as o', 'o.id', '=', 'oi.order_id')->join('products as p', 'p.id', '=', 'oi.product_id')->join('categories as c', 'c.id', '=', 'p.category_id')
            ->whereNotIn('o.status', ['cancelled', 'refunded'])->groupBy('c.id', 'c.name')->selectRaw('c.name, sum(oi.line_total) as revenue, sum(oi.quantity) as qty')->orderByDesc('revenue')->limit(5)->get();

        return view('admin.dashboard', [
            'cards' => [
                ['Total Revenue', money($valid()->sum('total')), 'Excludes cancelled & refunded'],
                ['Total Orders', Order::count(), $valid()->count() . ' active'],
                ['Total Products', Product::count(), Product::where('is_active', true)->count() . ' published'],
                ['Total Customers', Customer::count(), ''],
                ['Pending Orders', Order::where('status', 'pending')->count(), 'Need attention'],
                ['Low Stock Products', Product::where('manage_stock', true)->where('stock_qty', '<=', DB::raw('low_stock_threshold'))->doesntHave('variants')->count()
                    + Product::whereHas('variants', fn ($v) => $v->where('is_active', true)->where('stock_qty', '<=', 5))->count(), 'At or below threshold'],
            ],
            'series' => $series, 'top' => $top, 'topCats' => $topCats,
            'recentOrders' => Order::latest()->limit(8)->get(), 'recentCustomers' => Customer::latest()->limit(6)->get(),
            'revenue30' => (float) $series->sum('revenue'), 'orders30' => (int) $series->sum('orders'),
        ]);
    }
}
