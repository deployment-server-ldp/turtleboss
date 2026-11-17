<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $r)
    {
        $q = Customer::query()->withCount('orders')->withSum(['orders as spent' => fn ($o) => $o->whereNotIn('status', ['cancelled', 'refunded'])], 'total');
        if ($t = trim((string) $r->q)) $q->where(fn ($w) => $w->where('name', 'like', "%$t%")->orWhere('email', 'like', "%$t%")->orWhere('phone', 'like', "%$t%"));
        $sort = $r->sort === 'spent' ? 'spent' : 'created_at';
        return view('admin.customers.index', ['customers' => $q->orderByDesc($sort)->paginate(20)->withQueryString()]);
    }
    public function show(Customer $customer)
    {
        $customer->load('user', 'addresses');
        return view('admin.customers.show', ['customer' => $customer, 'orders' => $customer->orders()->latest()->get()]);
    }
    public function update(Request $r, Customer $customer)
    {
        $d = $r->validate(['name' => 'required|max:100', 'email' => 'required|email|max:150', 'phone' => 'nullable|max:32', 'notes' => 'nullable|max:2000', 'is_active' => 'nullable|boolean']);
        $customer->update(\Illuminate\Support\Arr::only($d, ['name', 'email', 'phone', 'notes']));
        if ($customer->user) {
            if ($customer->user->isStaff() && !auth()->user()->hasPermission('users')) abort(403);
            $customer->user->update(['name' => $d['name'], 'phone' => $d['phone'] ?? null, 'is_active' => $r->boolean('is_active')]);
        }
        return back()->with('status', 'Customer updated.');
    }
    public function export()
    {
        return response()->streamDownload(function () {
            $o = fopen('php://output', 'w');
            fputcsv($o, ['Name', 'Email', 'Phone', 'Orders', 'Total spent', 'Registered']);
            Customer::withCount('orders')->withSum(['orders as spent' => fn ($q) => $q->whereNotIn('status', ['cancelled', 'refunded'])], 'total')->chunk(200, function ($c) use ($o) {
                foreach ($c as $x) fputcsv($o, [$x->name, $x->email, $x->phone, $x->orders_count, $x->spent ?? 0, $x->created_at->toDateString()]);
            });
            fclose($o);
        }, 'turtleboss-customers-' . date('Ymd') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
