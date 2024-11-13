<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role; //it will use the from permission modal
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoleInPermissionController extends Controller
{
    public function groupRoleAndPermissionView()
    {
        return view('user_permissions.role_and_permission.role_and_permission_view');
    }

    public function groupRoleAndPermission()
    {
        $roles = Role::all();
        $permissionsData = Permission::all()->groupBy('group_name');
        return view('user_permissions.role_and_permission.role_and_permission', compact('roles', 'permissionsData'));
    }


    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'role_id' => 'required|integer|exists:roles,id|unique:role_has_permissions,role_id',
                'permission_id' => 'required|array',
                'permission_id.*' => 'integer|exists:permissions,id', // Validate each permission ID
            ],

            // Custom validation messages
            [
                'role_id.required' => 'Please select the Role.',
                'role_id.unique' => 'This role has already been assigned.',
                'role_id.exists' => 'The selected Role does not exist.',
                'permission_id.required' => 'Please select at least one Permission.',
                'permission_id.*.exists' => 'This Permission Value does not exist.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $roleId = $request->role_id;
        $permissionIds = $request->permission_id;

        $rolePermissions = [];

        foreach ($permissionIds as $permissionId) {
            $rolePermissions[] = [
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                // 'status' => '1',
                // 'created_at' => now(),
                // 'updated_at' => now(),
            ];
        }

        // Check if $rolePermissions is not empty before inserting
        if (!empty($rolePermissions)) {
            DB::table('role_has_permissions')->insert($rolePermissions);

            return response()->json([
                'status' => 200,
                'message' => "Permission Assigned To The Selected Role Successfully!"
            ]);
        } else {
            return response()->json([
                'status' => 400,
                'message' => "No permissions were assigned because no valid role-permission mappings were found."
            ]);
        }
    }

    public function index()
    {
        $getValue = Permission::all();

        if ($getValue->count() > 0) {
            // Group permissions by group_name
            $groupedPermissions = $getValue->groupBy('group_name');

            // Format the response
            $response = [];
            foreach ($groupedPermissions as $groupName => $permissions) {
                $permissionNames = $permissions->pluck('name'); // Get only the names

                $response[] = [
                    'group_name' => $groupName,
                    'permissions' => $permissionNames
                ];
            }

            return response()->json([
                'status' => 200,
                'message' => $response
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Records Found!"
            ]);
        }
    }

    // public function edit(int $id)
    // {
    //     $role = Role::findOrFail($id);

    //     // Retrieve all permissions and group them by 'group_name'
    //     $permissions = Permission::all();
    //     $permissionsData = $permissions->groupBy('group_name');

    //     if ($permissions->isEmpty() || $permissionsData->isEmpty()) {
    //         return response()->json([
    //             'status' => 404,
    //             'message' => 'No permissions assigned.'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'role' => $role,
    //         'permissions' => $permissions,
    //         'permission_group' => $permissionsData,
    //     ]);
    // }

    public function edit($role_id)
    {
        // Retrieve the specified role with its associated permissions
        $rolePermissions = DB::table('role_has_permissions')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->where('roles.id', $role_id) // Filter by role_id
            ->select('roles.id as role_id', 'roles.name as role_name', 'permissions.id as permission_id', 'permissions.name as permission_name')
            ->get();

            $permissions = Permission::all();
            $permissionsData = $permissions->groupBy('group_name');

        // Check if role exists and has permissions
        if ($rolePermissions->isEmpty()) {
            return response()->json([
                'status' => 404,
                'message' => 'Role not found or no permissions assigned.'
            ], 404);
        } else {
            // Format the result to match the required structure
            $result = [
                'role_id' => $rolePermissions->first()->role_id,
                'role_name' => $rolePermissions->first()->role_name,
                'permissionsData' => $permissionsData,

                'permissions' => $rolePermissions->map(function ($permission) {
                    return [
                        'permission_id' => $permission->permission_id,
                        'name' => $permission->permission_name,
                    ];
                })->values()->all(),
            ];

            return response()->json([
                'status' => 200,
                'values' => $result
            ]);
        }
    }

    public function groupRoleAndPermissionList()
    {
        // Retrieve roles with their associated permissions grouped by role
        $rolePermissions = DB::table('role_has_permissions')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
            ->select('roles.id as role_id', 'roles.name as role_name', 'permissions.id as permission_id', 'permissions.name as permission_name')
            ->get()
            ->groupBy('role_id'); // Group by role_id to structure data for each role

        // Format data to match the required output structure
        $result = $rolePermissions->map(function ($permissions, $roleId) {
            return [
                'role_id' => $roleId,
                'role_name' => $permissions->first()->role_name,
                'permissions' => $permissions->map(function ($permission) {
                    return [
                        'permission_id' => $permission->permission_id,
                        'name' => $permission->permission_name,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json([
            'status' => 200,
            'values' => $result
        ]);
    }


    public function destroy(int $role_id)
    {
        $getValue = DB::table('role_has_permissions')->where('role_id', $role_id)->first();
        if ($getValue) {
            DB::table('role_has_permissions')->where('role_id', $role_id)->delete();
            return response()->json([
                'status' => 200,
                'message' => "Role & Permissions Details Deleted Successfully!"
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Role & Permissions Found!"
            ]);
        }
    }

}
