<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role; //it will use the role from permission modal
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{

    function __construct()
    {
        $this->middleware('permission:view role', ['only' => ['index', 'show', 'role']]);
        $this->middleware('permission:create role', ['only' => ['store']]);
        $this->middleware('permission:edit role', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete role', ['only' => ['destroy']]);
    }

    public function role()
    {
        return view('role.role');
    }

    public function index()
    {
        $roles = Role::select('id', 'name', 'key')->get(); // Include key

        if ($roles->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'message' => $roles
            ]);
        }

        return response()->json(['status' => 404, 'message' => 'No Roles Found!']);
    }


    public function SelectRoleNameDropdown()
    {
        // Get roles that current user can assign based on hierarchy
        $user = auth()->user();
        $isMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        
        if ($isMasterSuperAdmin) {
            $roles = Role::select('id', 'name')->get();
        } else {
            $roles = Role::select('id', 'name')->where('name', '!=', 'Master Super Admin')->get();
        }

        // Check if the collection is not empty
        if ($roles->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'roles' => $roles,
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Records Found!"
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:roles,name',
            'key'  => 'required|string|in:super_admin,admin,manager,sales_rep,cashier,pos_user,retail_user|unique:roles,key',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'key' => $request->key,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Role created successfully!'
        ]);
    }

    /**
     * Display the specified resource.

     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        $getValue = Role::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Role Found!"
            ]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $role = Role::select('id', 'name', 'key')->find($id);
        if ($role) {
            return response()->json(['status' => 200, 'message' => $role]);
        }
        return response()->json(['status' => 404, 'message' => 'Role not found.']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json(['status' => 404, 'message' => 'Role not found.']);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|unique:roles,name,' . $id,
            'key'  => 'required|string|in:super_admin,admin,manager,sales_rep,cashier,pos_user,retail_user|unique:roles,key,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        $role->update([
            'name' => $request->name,
            'key' => $request->key,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Role updated successfully!'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $currentUser = auth()->user();
        $roleToDelete = Role::find($id);

        if (!$roleToDelete) {
            return response()->json([
                'status' => 404,
                'message' => "No Such Role Found!"
            ]);
        }

        // Prevent deletion of Master Super Admin role by non-Master Super Admin users
        $currentUserIsMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        
        if ($roleToDelete->name === 'Master Super Admin' && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to delete the Master Super Admin role."
            ], 403);
        }

        // Prevent deletion of critical system roles
        $criticalRoles = ['Master Super Admin', 'Super Admin'];
        if (in_array($roleToDelete->name, $criticalRoles)) {
            return response()->json([
                'status' => 403,
                'message' => "Cannot delete critical system role '{$roleToDelete->name}'. This role is essential for system operation."
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

        // Proceed with deletion
        $roleToDelete->delete();
        
        return response()->json([
            'status' => 200,
            'message' => "Role Details Deleted Successfully!"
        ]);
    }
}
