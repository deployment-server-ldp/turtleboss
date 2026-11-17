<?php

use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\SecurityHeaders;
use App\Models\Redirect;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['staff' => EnsureStaff::class]);
        $middleware->web(append: [SecurityHeaders::class]);
        $middleware->redirectGuestsTo(fn (Request $r) => $r->is('admin*') ? route('admin.login') : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
        // Managed 301/302 redirects (broken-link prevention): consult the table on any 404.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (!$request->isMethod('GET')) return null;
            try {
                $r = Redirect::where('is_active', true)->where('from_path', '/' . trim($request->path(), '/'))->first();
            } catch (\Throwable) { return null; }
            if ($r) {
                $r->increment('hits');
                return redirect($r->to_url, $r->status_code);
            }
            return null;
        });
    })->create();
