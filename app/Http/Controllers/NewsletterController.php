<?php
namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $r)
    {
        $d = $r->validate(['email' => 'required|email|max:150']);
        NewsletterSubscriber::firstOrCreate(['email' => strtolower($d['email'])]);
        return $r->expectsJson() ? response()->json(['ok' => true, 'message' => 'Thanks for subscribing!']) : back()->with('status', 'Thanks for subscribing!');
    }
    public function contact(Request $r)
    {
        $d = $r->validate(['name' => 'required|max:100', 'email' => 'required|email|max:150', 'phone' => 'nullable|max:32', 'message' => 'required|min:5|max:3000', 'website' => 'max:0']);
        unset($d['website']); // honeypot
        ContactMessage::create($d);
        return back()->with('status', 'Thank you! We received your message and will reply soon.');
    }
}
