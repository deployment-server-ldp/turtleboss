<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $r = $next($request);
        $r->headers->set('X-Content-Type-Options', 'nosniff');
        $r->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $r->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $r->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->secure()) $r->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        if ($request->is('admin*')) $r->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $r;
    }
}
