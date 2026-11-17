<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class WishlistController extends Controller
{
    public static function current(bool $create = false): ?Wishlist
    {
        $uid = Auth::id();
        $w = $uid ? Wishlist::where('user_id', $uid)->first() : Wishlist::whereNull('user_id')->where('session_id', Session::getId())->first();
        if (!$w && $create) $w = Wishlist::create(['user_id' => $uid, 'session_id' => $uid ? null : Session::getId()]);
        return $w;
    }
    public static function ids(): array { return self::current()?->items()->pluck('product_id')->all() ?? []; }

    public static function mergeGuest(string $oldSession, int $userId): void
    {
        $g = Wishlist::whereNull('user_id')->where('session_id', $oldSession)->first();
        if (!$g) return;
        $mine = Wishlist::firstOrCreate(['user_id' => $userId]);
        foreach ($g->items as $i) $mine->items()->firstOrCreate(['product_id' => $i->product_id]);
        $g->delete();
    }

    public function index()
    {
        $products = Product::active()->with('images', 'variants')->whereIn('id', self::ids())->get();
        return view('wishlist.index', ['products' => $products, 'seo' => Seo::make(['title' => 'My Wishlist', 'noindex' => true])]);
    }

    public function toggle(Request $r)
    {
        $d = $r->validate(['product_id' => 'required|integer|exists:products,id']);
        $w = self::current(true);
        $item = $w->items()->where('product_id', $d['product_id'])->first();
        if ($item) { $item->delete(); $added = false; } else { $w->items()->create(['product_id' => $d['product_id']]); $added = true; }
        return response()->json(['added' => $added, 'ids' => self::ids(), 'count' => count(self::ids())]);
    }
}
