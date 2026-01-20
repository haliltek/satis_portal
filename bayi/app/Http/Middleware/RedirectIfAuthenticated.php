<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            // Subdirectory desteği için tam URL oluştur
            $basePath = '/b2b-gemas-project-main/bayi/public';
            $protocol = $request->getScheme();
            $host = $request->getHost();
            $homeUrl = $protocol . '://' . $host . $basePath . '/home';
            return redirect($homeUrl);
        }

        return $next($request);
    }
}
