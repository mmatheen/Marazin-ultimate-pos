<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class PreventSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Regenerate CSRF token for every request to prevent expiration
        if ($request->isMethod('get') && !$request->ajax()) {
            Session::regenerateToken();
        }
        
        // Set custom session lifetime for login attempts
        if ($request->is('login') && $request->isMethod('post')) {
            config(['session.lifetime' => 60]); // 1 hour for login attempts
        }

        return $next($request);
    }
}