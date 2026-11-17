<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    private function customer()
    {
        $u = auth()->user();
        return $u->customer ?? $u->customer()->create(['name' => $u->name, 'email' => $u->email, 'phone' => $u->phone]);
    }
    private function view(string $v, array $d = [], string $title = 'My Account') { return view("account.$v", $d + ['seo' => Seo::make(['title' => $title, 'noindex' => true])]); }

    public function index()
    {
        $orders = Order::where('user_id', auth()->id())->latest()->limit(3)->get();
        return $this->view('index', ['orders' => $orders, 'orderCount' => Order::where('user_id', auth()->id())->count()]);
    }
    public function orders() { return $this->view('orders', ['orders' => Order::where('user_id', auth()->id())->latest()->paginate(10)], 'My Orders'); }
    public function order(string $number)
    {
        $o = Order::with('items', 'shipments')->where('user_id', auth()->id())->where('number', $number)->firstOrFail();
        return $this->view('order', ['order' => $o], "Order $number");
    }

    public function profile() { return $this->view('profile', ['user' => auth()->user()], 'Profile'); }
    public function updateProfile(Request $r)
    {
        $d = $r->validate(['name' => 'required|max:100', 'email' => 'required|email|max:150|unique:users,email,' . auth()->id(), 'phone' => 'nullable|max:32']);
        auth()->user()->update($d);
        $this->customer()->update($d);
        return back()->with('status', 'Profile updated.');
    }
    public function password(Request $r)
    {
        $r->validate(['current_password' => 'required|current_password', 'password' => 'required|min:8|confirmed']);
        auth()->user()->update(['password' => $r->password]);
        return back()->with('status', 'Password changed.');
    }

    public function addresses() { return $this->view('addresses', ['addresses' => $this->customer()->addresses, 'cities' => \App\Services\ShippingService::cities(), 'provinces' => \App\Services\ShippingService::provinces()], 'Saved Addresses'); }
    public function saveAddress(Request $r, ?int $id = null)
    {
        $d = $r->validate(['label' => 'nullable|max:40', 'name' => 'required|max:100', 'phone' => 'required|max:32', 'address' => 'required|max:255', 'city' => 'required|max:80', 'province' => 'nullable|max:80', 'postal_code' => 'nullable|max:16']);
        $c = $this->customer();
        $addr = $id ? $c->addresses()->findOrFail($id) : $c->addresses()->make();
        $addr->fill($d + ['customer_id' => $c->id])->save();
        if ($r->boolean('is_default') || $c->addresses()->count() === 1) { $c->addresses()->update(['is_default' => false]); $addr->update(['is_default' => true]); }
        return back()->with('status', 'Address saved.');
    }
    public function deleteAddress(int $id)
    {
        $this->customer()->addresses()->findOrFail($id)->delete();
        return back()->with('status', 'Address removed.');
    }
}
