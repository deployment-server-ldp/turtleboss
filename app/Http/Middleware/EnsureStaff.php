<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Gate for /admin: authenticated, active staff user holding the given module permission. */
class EnsureStaff
{
    public function handle(Request $request, Closure $next, ?string $permission = null)
    {
        $user = $request->user();
        if (!$user) return redirect()->guest(route('admin.login'));
        if (!$user->isStaff() || !$user->is_active) abort(403);
        if ($permission && !$user->hasPermission($permission)) abort(403, 'You do not have permission to access this section.');
        return $next($request);
    }
}
