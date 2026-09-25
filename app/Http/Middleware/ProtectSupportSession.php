<?php
namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProtectSupportSession
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->session()->has('impersonator_id')) {
            $operator = User::find($request->session()->get('impersonator_id'));
            if (! $operator || $operator->suspended_at || ! $operator->hasVerifiedEmail() || ! $operator->hasRole('super-admin') || $request->session()->get('impersonation_expires_at', 0) < now()->timestamp || ! hash_equals(hash_hmac('sha256', $operator->getAuthPassword(), config('app.key')), (string) $request->session()->get('impersonator_auth_hash', ''))) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                abort(403, 'This support session has expired. Sign in again to continue.');
            }
            if (! $request->is('support/stop')) {
                abort_if(!$request->isMethodSafe() || $request->is('settings*', 'admin*', 'api*', 'horizon*', 'email/*'), 403, 'Support sessions are read-only. Return to your administrator account to make changes.');
            }
        }
        return $next($request);
    }
}
