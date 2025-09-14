<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view role-permission', ['only' => ['groupRoleAndPermissionView', 'groupRoleAndPermissionList', 'groupRoleAndPermission']]);
        $this->middleware('permission:create role-permission', ['only' => ['store']]);
        $this->middleware('permission:edit role-permission', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete role-permission', ['only' => ['destroy']]);
        $this->middleware('role.security', ['only' => ['edit', 'update', 'store', 'destroy']]);
    }

    public function groupRoleAndPermissionView()
    {
        return view('role_and_permission.role_and_permission_view');
    }


    public function groupRoleAndPermission()
    {
        $user = auth()->user();
        
        // Check if user is Master Super Admin
        $isMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        
        // Get roles that current user can see
        if ($isMasterSuperAdmin) {
            $roles = Role::all();
        } else {
            $roles = Role::where('name', '!=', 'Master Super Admin')->get();
        }
        
        // Filter permissions based on user role and their actual permissions
        if ($isMasterSuperAdmin) {
            // Master Super Admin sees all permissions
            $allPermissions = Permission::all();
        } else {
            // Other users only see permissions they actually have been granted
            // Get both direct permissions and permissions through roles
            $directPermissions = $user->permissions;
            $rolePermissions = collect();
            
            foreach ($user->roles as $role) {
                $rolePermissions = $rolePermissions->merge($role->permissions);
            }
            
            // Merge and remove duplicates
            $allPermissions = $directPermissions->merge($rolePermissions)->unique('id');
        }
        
        $permissionsData = $allPermissions->groupBy('group_name');
        
        return view('role_and_permission.role_and_permission', compact('roles', 'permissionsData'));
    }

    public function groupRoleAndPermissionList()
    {
        $user = auth()->user();
        $isMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        
        // Get roles that current user can see
        if ($isMasterSuperAdmin) {
            $roles = Role::with('permissions')->get();
        } else {
            $roles = Role::where('name', '!=', 'Master Super Admin')->with('permissions')->get();
        }

        // Format data to match the required output structure
        $result = $roles->map(function ($role) {
            return [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'permission_id' => $permission->id,
                        'name' => $permission->name,
                    ];
                })->values()->all(),
            ];
        });

        return response()->json([
            'status' => 200,
            'values' => $result
        ]);
    }



    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|integer|exists:roles,id',
            'permission_id' => 'required|array',
            'permission_id.*' => 'integer|exists:permissions,id',
        ], [
            'role_id.required' => 'Please select the Role.',
            'role_id.exists' => 'The selected Role does not exist.',
            'permission_id.required' => 'Please select at least one Permission.',
            'permission_id.*.exists' => 'This Permission Value does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        // Find the role
        $role = Role::find($request->role_id);

        if (!$role) {
            return response()->json([
                'status' => 404,
                'message' => 'Role not found!'
            ], 404);
        }

        // Check if current user can assign to this role
        $currentUser = auth()->user();
        $isMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        
        if (!$isMasterSuperAdmin && $role->name === 'Master Super Admin') {
            return response()->json([
                'status' => 403,
                'message' => 'You do not have permission to modify Master Super Admin role.'
            ], 403);
        }

        // Prevent users from editing their own role
        $userHasThisRole = $currentUser->roles->where('id', $role->id)->count() > 0;
        if ($userHasThisRole) {
            return response()->json([
                'status' => 403,
                'message' => 'You cannot edit your own role. This could lead to access issues.'
            ], 403);
        }

        // Get selected permissions
        $selectedPermissions = Permission::whereIn('id', $request->permission_id)->get();
        
        // For non-Master Super Admin and non-Super Admin users, validate they can only assign permissions they have
        $isSuperAdmin = $currentUser->roles->where('key', 'super_admin')->count() > 0;
        
        if (!$isMasterSuperAdmin && !$isSuperAdmin) {
            // Get all permissions the current user has (direct + via roles)
            $userDirectPermissions = $currentUser->permissions;
            $userRolePermissions = collect();
            
            foreach ($currentUser->roles as $userRole) {
                $userRolePermissions = $userRolePermissions->merge($userRole->permissions);
            }
            
            $allUserPermissions = $userDirectPermissions->merge($userRolePermissions)->unique('id');
            $userPermissionIds = $allUserPermissions->pluck('id')->toArray();
            
            $invalidPermissions = $selectedPermissions->filter(function($permission) use ($userPermissionIds) {
                return !in_array($permission->id, $userPermissionIds);
            });

            if ($invalidPermissions->count() > 0) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You can only assign permissions that you have yourself. Invalid permissions: ' . $invalidPermissions->pluck('name')->join(', ')
                ], 403);
            }
        }

        $permissions = $selectedPermissions->pluck('name')->toArray();

        // Get the current permissions of the role
        $currentPermissions = $role->permissions->pluck('name')->toArray();

        // Check if user is authorized to assign permissions (Master Super Admin or Super Admin)
        $isSuperAdmin = $currentUser->roles->where('key', 'super_admin')->count() > 0;
        
        // Both Master Super Admin and Super Admin can update role permissions
        if (!$isMasterSuperAdmin && !$isSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => 'You do not have permission to assign permissions to roles. Only Super Admin or Master Super Admin can assign permissions.'
            ], 403);
        }

        // Always sync permissions - this handles adding, removing, or keeping the same permissions
        $role->syncPermissions($permissions);

        // Check what actually changed to provide appropriate message
        $newPermissions = $role->fresh()->permissions->pluck('name')->toArray();
        $addedPermissions = array_diff($newPermissions, $currentPermissions);
        $removedPermissions = array_diff($currentPermissions, $newPermissions);

        if (empty($addedPermissions) && empty($removedPermissions)) {
            $message = "Permissions verified and confirmed for the selected role.";
        } else {
            $message = "Permissions updated successfully for the selected role!";
        }

        return response()->json([
            'status' => 200,
            'message' => $message
        ]);
    }

    public function edit($role_id)
    {
        // Find the role and its permissions
        $role = Role::with('permissions')->find($role_id);

        if (!$role) {
            // For AJAX requests, return JSON response
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Role not found!'
                ], 404);
            }
            return response()->json([
                'status' => 404,
                'message' => 'Role not found!'
            ], 404);
        }

        $user = auth()->user();
        
        // Check if user is Master Super Admin
        $isMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        
        // Prevent non-Master Super Admin users from editing Master Super Admin role
        if (!$isMasterSuperAdmin && $role->name === 'Master Super Admin') {
            // For AJAX requests, return JSON response for toastr
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Access denied! You do not have permission to edit Master Super Admin role.',
                    'show_toastr' => true
                ], 403);
            }
            return response()->json([
                'status' => 403,
                'message' => 'Access denied! You do not have permission to edit Master Super Admin role.',
                'show_toastr' => true
            ], 403);
        }

        // Prevent users from editing their own role
        $userHasThisRole = $user->roles->where('id', $role->id)->count() > 0;
        if ($userHasThisRole) {
            // For AJAX requests, return JSON response for toastr
            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Permission denied! You cannot edit your own role. This could lead to access issues.',
                    'show_toastr' => true
                ], 403);
            }
            return response()->json([
                'status' => 403,
                'message' => 'Permission denied! You cannot edit your own role. This could lead to access issues.',
                'show_toastr' => true
            ], 403);
        }
        
        // Filter permissions based on user role and their actual permissions
        if ($isMasterSuperAdmin) {
            // Master Super Admin sees all permissions
            $allPermissions = Permission::all();
        } else {
            // Other users only see permissions they actually have been granted
            // Get both direct permissions and permissions through roles
            $directPermissions = $user->permissions;
            $rolePermissions = collect();
            
            foreach ($user->roles as $userRole) {
                $rolePermissions = $rolePermissions->merge($userRole->permissions);
            }
            
            // Merge and remove duplicates
            $allPermissions = $directPermissions->merge($rolePermissions)->unique('id');
        }

        // Example grouping by permission type or category (adjust according to your DB structure)
        $permissionsData = $allPermissions->groupBy('group_name'); // Assuming 'group_name' is a column in the 'permissions' table

        return view('role_and_permission.role_and_permission_edit', [
            'role' => $role,
            'permissionsData' => $permissionsData,
            'selectedRoleId' => $role->id, // Pass the selected role's ID to the view
        ]);
    }


    public function update(Request $request, $role_id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'permission_id' => 'required|array',
            'permission_id.*' => 'integer|exists:permissions,id',
        ], [
            'permission_id.required' => 'Please select at least one Permission.',
            'permission_id.*.exists' => 'This Permission Value does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        // Find the role
        $role = Role::find($role_id);

        if (!$role) {
            return response()->json([
                'status' => 404,
                'message' => 'Role not found!'
            ], 404);
        }

        $currentUser = auth()->user();
        $isMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        
        // Prevent non-Master Super Admin users from editing Master Super Admin role
        if (!$isMasterSuperAdmin && $role->name === 'Master Super Admin') {
            return response()->json([
                'status' => 403,
                'message' => 'You do not have permission to modify Master Super Admin role.'
            ], 403);
        }

        // Prevent users from editing their own role
        $userHasThisRole = $currentUser->roles->where('id', $role->id)->count() > 0;
        if ($userHasThisRole) {
            return response()->json([
                'status' => 403,
                'message' => 'You cannot edit your own role. This could lead to access issues.'
            ], 403);
        }

        // Get selected permissions
        $selectedPermissions = Permission::whereIn('id', $request->permission_id)->get();
        
        // For non-Master Super Admin and non-Super Admin users, validate they can only assign permissions they have
        $isSuperAdmin = $currentUser->roles->where('key', 'super_admin')->count() > 0;
        
        if (!$isMasterSuperAdmin && !$isSuperAdmin) {
            // Get all permissions the current user has (direct + via roles)
            $userDirectPermissions = $currentUser->permissions;
            $userRolePermissions = collect();
            
            foreach ($currentUser->roles as $userRole) {
                $userRolePermissions = $userRolePermissions->merge($userRole->permissions);
            }
            
            $allUserPermissions = $userDirectPermissions->merge($userRolePermissions)->unique('id');
            $userPermissionIds = $allUserPermissions->pluck('id')->toArray();
            
            $invalidPermissions = $selectedPermissions->filter(function($permission) use ($userPermissionIds) {
                return !in_array($permission->id, $userPermissionIds);
            });

            if ($invalidPermissions->count() > 0) {
                return response()->json([
                    'status' => 403,
                    'message' => 'You can only assign permissions that you have yourself. Invalid permissions: ' . $invalidPermissions->pluck('name')->join(', ')
                ], 403);
            }
        }

        $permissions = $selectedPermissions->pluck('name')->toArray();

        // Sync the permissions with the role
        $role->syncPermissions($permissions);

        return response()->json([
            'status' => 200,
            'message' => "Permissions updated successfully for the selected role!"
        ]);
    }



    public function destroy(int $role_id)
    {
        $currentUser = auth()->user();
        $roleToDelete = Role::find($role_id);

        if (!$roleToDelete) {
            return response()->json([
                'status' => 404,
                'message' => 'Role not found!'
            ], 404);
        }

        $currentUserIsMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        $currentUserIsSuperAdmin = $currentUser->roles->where('key', 'super_admin')->count() > 0;

        // Prevent deletion of Master Super Admin role by anyone, including Master Super Admin
        if ($roleToDelete->name === 'Master Super Admin') {
            return response()->json([
                'status' => 403,
                'message' => "Master Super Admin role cannot be deleted. This role is essential for system operation and security."
            ], 403);
        }

        // Prevent deletion of other critical system roles
        $criticalRoles = ['Super Admin'];
        if (in_array($roleToDelete->name, $criticalRoles)) {
            return response()->json([
                'status' => 403,
                'message' => "Cannot delete critical system role '{$roleToDelete->name}'. This role is essential for system operation."
            ], 403);
        }

        // Allow both Master Super Admin and Super Admin to delete roles (with restrictions)
        if (!$currentUserIsMasterSuperAdmin && !$currentUserIsSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to delete roles. Only Super Admin or Master Super Admin can delete roles."
            ], 403);
        }

        // Prevent deletion of role if current user has this role
        $currentUserHasThisRole = $currentUser->roles->where('id', $roleToDelete->id)->count() > 0;
        if ($currentUserHasThisRole) {
            return response()->json([
                'status' => 403,
                'message' => "You cannot delete a role that is assigned to your own account. This would cause access issues."
            ], 403);
        }

        // Check if role is assigned to any users
        $usersWithThisRole = User::whereHas('roles', function($query) use ($roleToDelete) {
            $query->where('id', $roleToDelete->id);
        })->count();

        if ($usersWithThisRole > 0) {
            return response()->json([
                'status' => 403,
                'message' => "Cannot delete role '{$roleToDelete->name}' because it is assigned to {$usersWithThisRole} user(s). Please remove this role from all users first."
            ], 403);
        }

        // Remove all permissions associated with this role
        $roleToDelete->permissions()->detach();

        // Delete the role
        $roleToDelete->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Role deleted successfully!'
        ]);
    }

    /**
     * Get permissions for a specific role (for auto-fetching in form)
     */
    public function getRolePermissions($role_id)
    {
        $role = Role::with('permissions')->find($role_id);

        if (!$role) {
            return response()->json([
                'status' => 404,
                'message' => 'Role not found!'
            ], 404);
        }

        $user = auth()->user();
        $isMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        
        // Prevent non-Master Super Admin users from accessing Master Super Admin role
        if (!$isMasterSuperAdmin && $role->name === 'Master Super Admin') {
            return response()->json([
                'status' => 403,
                'message' => 'Access denied! You do not have permission to view Master Super Admin role permissions.',
                'show_toastr' => true
            ], 403);
        }

        // Prevent users from viewing their own role permissions for editing
        $userHasThisRole = $user->roles->where('id', $role->id)->count() > 0;
        if ($userHasThisRole) {
            return response()->json([
                'status' => 403,
                'message' => 'Permission denied! You cannot modify your own role permissions.',
                'show_toastr' => true
            ], 403);
        }

        return response()->json([
            'status' => 200,
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(function($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'group_name' => $permission->group_name ?? 'default'
                    ];
                })
            ]
        ]);
    }
}
