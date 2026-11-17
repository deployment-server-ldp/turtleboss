<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WishlistItem;

class WishlistAnalyticsController extends Controller
{
    public function index()
    {
        $top = Product::withCount('wishlistItems_')->get()->sortByDesc('wishlist_items__count')->filter(fn ($p) => $p->wishlist_items__count > 0)->take(25);
        return view('admin.wishlist', ['top' => $top, 'total' => WishlistItem::count()]);
    }
}
