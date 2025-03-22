<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleAndPermissionCheckMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Check if user has at least one role or one permission
        if ($user && ($user->roles->isNotEmpty() || $user->permissions->isNotEmpty())) {
            return $next($request);
        }

        // If user has no roles/permissions, abort with 403
        abort(403, 'Unauthorized');
    }
}
