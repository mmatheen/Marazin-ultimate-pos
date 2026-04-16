<?php

namespace App\Http\Middleware;

use App\Services\User\UserAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MasterAdminOnly
{
    public function __construct(private readonly UserAccessService $userAccessService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$this->userAccessService->isMasterSuperAdmin($user)) {
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
