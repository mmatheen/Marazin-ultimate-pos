<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MasterAdminOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || !auth()->user()->isMasterSuperAdmin()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Access denied. Master Super Admin privileges required.'
                ], 403);
            }
            
            abort(403, 'Access denied. Master Super Admin privileges required.');
        }

        return $next($request);
    }
}
