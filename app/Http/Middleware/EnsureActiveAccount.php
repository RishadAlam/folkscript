<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        $returningFromSupport = $request->hasSession() && $request->session()->has('impersonator_id') && $request->is('support/stop');
        if ($request->user()?->suspended_at && ! $returningFromSupport && ! $request->routeIs('logout')) {
            abort(403, 'This account is suspended.');
        }
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        if ($request->user() || $request->is('login', 'register', 'settings*', 'write*', 'dashboard*', 'admin*', 'forgot-password', 'reset-password*', 'confirm-password', 'two-factor-challenge', 'email/*', 'api/v1/me*', 'api/v1/tokens*')) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }
        return $response;
    }
}
