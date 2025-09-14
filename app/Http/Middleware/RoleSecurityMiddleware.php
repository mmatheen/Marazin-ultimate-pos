<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleSecurityMiddleware
{
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

        $isMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        $isSuperAdmin = $user->roles->where('key', 'super_admin')->count() > 0;

        // For role management routes
        if ($request->routeIs('role.*') || $request->routeIs('role-permission.*')) {
            
            // Get role ID from route parameters
            $roleId = $request->route('id') ?? $request->route('role_id');
            
            if ($roleId) {
                $targetRole = \Spatie\Permission\Models\Role::find($roleId);
                
                if ($targetRole) {
                    // Prevent non-Master Super Admin from accessing Master Super Admin role operations
                    if (!$isMasterSuperAdmin && $targetRole->name === 'Master Super Admin') {
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
        if ($request->routeIs('user.*')) {
            
            // Get user ID from route parameters
            $userId = $request->route('id');
            
            if ($userId) {
                $targetUser = \App\Models\User::with('roles')->find($userId);
                
                if ($targetUser) {
                    $targetUserIsMasterSuperAdmin = $targetUser->roles->where('name', 'Master Super Admin')->count() > 0;
                    $targetUserIsSuperAdmin = $targetUser->roles->where('key', 'super_admin')->count() > 0;
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
}