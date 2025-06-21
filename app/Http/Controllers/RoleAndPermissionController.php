<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view role & permission', ['only' => ['groupRoleAndPermissionView', 'groupRoleAndPermissionList', 'groupRoleAndPermission']]);
        $this->middleware('permission:create role & permission', ['only' => ['store']]);
        $this->middleware('permission:edit role & permission', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete role & permission', ['only' => ['destroy']]);
    }

    public function groupRoleAndPermissionView()
    {
        return view('role_and_permission.role_and_permission_view');
    }


    public function groupRoleAndPermission()
    {
        $roles = Role::all();
        $permissionsData = Permission::all()->groupBy('group_name');
        return view('role_and_permission.role_and_permission', compact('roles', 'permissionsData'));
    }

    public function groupRoleAndPermissionList()
    {
        // Retrieve all roles with their associated permissions
        $roles = Role::with('permissions')->get();

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

        // Get selected permissions
        $permissions = Permission::whereIn('id', $request->permission_id)->pluck('name')->toArray();

        // Get the current permissions of the role
        $currentPermissions = $role->permissions->pluck('name')->toArray();

        // If the role already has all the permissions, do nothing
        if (empty(array_diff($permissions, $currentPermissions))) {
            return response()->json([
                'status' => 404,
                'message' => 'This role already has the selected permissions.'
            ]);
        }

        // Assign permissions to role using Spatie
        $role->syncPermissions($permissions);

        return response()->json([
            'status' => 200,
            'message' => "Permissions assigned successfully to the selected role!"
        ]);
    }

    public function edit($role_id)
    {
        // Find the role and its permissions
        $role = Role::with('permissions')->find($role_id);

        if (!$role) {
            return response()->json([
                'status' => 404,
                'message' => 'Role not found!'
            ], 404);
        }

        // Get all roles
        $roles = Role::all(); // Fetch all roles for the dropdown

        // Get permissions, optionally grouped by their 'type' or 'group' attribute
        $permissions = Permission::all(); // Or group them if necessary

        // Example grouping by permission type or category (adjust according to your DB structure)
        $permissionsData = $permissions->groupBy('group_name'); // Assuming 'group_name' is a column in the 'permissions' table

        return view('role_and_permission.role_and_permission_edit', [
            'role' => $role,
            'roles' => $roles, // Pass the roles to the view
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

        // Get selected permissions
        $permissions = Permission::whereIn('id', $request->permission_id)->pluck('name')->toArray();


        // Sync the permissions with the role
        $role->syncPermissions($permissions);

        return response()->json([
            'status' => 200,
            'message' => "Permissions updated successfully for the selected role!"
        ]);
    }



    public function destroy(int $role_id)
    {
        // Find the role by ID
        $role = Role::find($role_id);

        if (!$role) {
            return response()->json([
                'status' => 404,
                'message' => 'Role not found!'
            ]);
        }

        // Remove all permissions associated with this role
        $role->permissions()->detach();

        // Delete the role
        $role->delete();

        return response()->json([
            'status' => 200,
            'message' => 'Role deleted successfully!'
        ]);
    }
}
