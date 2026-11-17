<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $r)
    {
        $q = Review::with('product')->latest();
        if ($r->status === 'pending') $q->where('is_approved', false);
        if ($r->status === 'approved') $q->where('is_approved', true);
        return view('admin.reviews.index', ['reviews' => $q->paginate(20)->withQueryString(), 'pending' => Review::where('is_approved', false)->count()]);
    }
    public function update(Request $r, Review $review)
    {
        $review->update(['is_approved' => $r->boolean('approve')]);
        return back()->with('status', $r->boolean('approve') ? 'Review approved.' : 'Review unapproved.');
    }
    public function destroy(Review $review)
    {
        if ($review->image) app(\App\Services\ImageService::class)->delete($review->image);
        $review->delete();
        return back()->with('status', 'Review deleted.');
    }
}
