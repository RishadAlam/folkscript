<?php

namespace App\Http\Middleware;

use App\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveRedirect
{
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), ['GET', 'HEAD'], true) && str_starts_with($request->getPathInfo(), '/@')) {
            $redirect = Redirect::query()->where('from_path', $request->getPathInfo())->first();
            if ($redirect && $redirect->to_path !== $request->getPathInfo() && preg_match('#^/(?!/)[^\r\n\\\\]*$#', $redirect->to_path)) {
                return redirect($redirect->to_path, in_array($redirect->status_code, [301, 302, 307, 308], true) ? $redirect->status_code : 301);
            }
        }

        return $next($request);
    }
}
