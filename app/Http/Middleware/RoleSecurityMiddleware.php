<?php

namespace App\Http\Middleware;

use App\Services\User\UserAccessService;
use Closure;
use Illuminate\Http\Request;

class RoleSecurityMiddleware
{
    public function __construct(private readonly UserAccessService $userAccessService)
    {
    }

    /**
     * Handle an incoming request for role and permission security.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 401,
                'message' => 'Authentication required.'
            ], 401);
        }

        $isMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($user);
        $isSuperAdmin = $this->userAccessService->isSuperAdmin($user);

        // For role management routes
        if ($this->isRoleManagementRoute($request)) {

            // Get role ID from route parameters
            $roleId = $request->route('id') ?? $request->route('role_id');

            if ($roleId) {
                $targetRole = \Spatie\Permission\Models\Role::find($roleId);

                if ($targetRole) {
                    // Prevent non-Master Super Admin from accessing Master Super Admin role operations
                    if (!$isMasterSuperAdmin && $this->userAccessService->isMasterSuperAdminRoleName($targetRole->name)) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Access denied. Insufficient permissions for this role.'
                        ], 403);
                    }

                    // Prevent users from editing their own role
                    $userHasThisRole = $user->roles->where('id', $targetRole->id)->count() > 0;
                    if ($userHasThisRole && in_array($request->method(), ['PUT', 'PATCH', 'POST'])) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'You cannot modify your own role.'
                        ], 403);
                    }
                }
            }
        }

        // For user management routes
        if ($this->isUserManagementRoute($request)) {

            // Get user ID from route parameters
            $userId = $request->route('id');

            if ($userId) {
                $targetUser = \App\Models\User::with('roles')->find($userId);

                if ($targetUser) {
                    $targetUserIsMasterSuperAdmin = $this->userAccessService->isMasterSuperAdmin($targetUser);
                    $isOwnProfile = $user->id === $targetUser->id;

                    // Prevent non-Master Super Admin from accessing Master Super Admin user operations
                    if (!$isMasterSuperAdmin && $targetUserIsMasterSuperAdmin) {
                        return response()->json([
                            'status' => 403,
                            'message' => 'Access denied. Insufficient permissions for this user.'
                        ], 403);
                    }

                    // Allow super admin and master admin to edit their own profile (except role changes)
                    if ($isOwnProfile && ($isMasterSuperAdmin || $isSuperAdmin)) {
                        // For edit operations, allow access to the form
                        if ($request->method() === 'GET') {
                            return $next($request);
                        }

                        // For update operations, check if trying to change role
                        if (in_array($request->method(), ['PUT', 'PATCH', 'POST'])) {
                            $currentRole = $user->roles->first()?->name;
                            if ($request->has('roles') && $request->roles !== $currentRole) {
                                return response()->json([
                                    'status' => 403,
                                    'message' => 'You cannot change your own role. Please contact another administrator.'
                                ], 403);
                            }
                            // Allow other profile updates
                            return $next($request);
                        }

                        // Prevent self-deletion
                        if ($request->method() === 'DELETE') {
                            return response()->json([
                                'status' => 403,
                                'message' => 'You cannot delete your own account.'
                            ], 403);
                        }
                    }
                }
            }
        }

        return $next($request);
    }

    /**
     * Detect role management routes reliably (named + unnamed routes).
     */
    private function isRoleManagementRoute(Request $request): bool
    {
        if ($request->routeIs('role.*') || $request->routeIs('role-permission.*')) {
            return true;
        }

        $uri = (string) ($request->route()?->uri() ?? '');
        if (str_starts_with($uri, 'role') || str_starts_with($uri, 'group-role-and-permission') || str_starts_with($uri, 'role-and-permission')) {
            return true;
        }

        $actionName = (string) ($request->route()?->getActionName() ?? '');

        return str_contains($actionName, 'RoleController@') || str_contains($actionName, 'RoleAndPermissionController@');
    }

    /**
     * Detect user management routes reliably (named + unnamed routes).
     */
    private function isUserManagementRoute(Request $request): bool
    {
        if ($request->routeIs('user.*')) {
            return true;
        }

        $uri = (string) ($request->route()?->uri() ?? '');
        if (str_starts_with($uri, 'user')) {
            return true;
        }

        $actionName = (string) ($request->route()?->getActionName() ?? '');

        return str_contains($actionName, 'UserController@');
    }
}
