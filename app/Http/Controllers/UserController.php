<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:view user', ['only' => ['index', 'show', 'user']]);
        $this->middleware('permission:create user', ['only' => ['store']]);
        $this->middleware('permission:edit user', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete user', ['only' => ['destroy']]);
    }

    public function user()
    {
        return view('user.user');
    }

    // app/Http/Controllers/UserController.php

    public function index()
    {
        // Get users that current user can see based on hierarchy
        $user = auth()->user();
        $isMasterSuperAdmin = $user->roles->where('name', 'Master Super Admin')->count() > 0;
        
        if ($isMasterSuperAdmin) {
            $users = User::with(['roles', 'locations'])->get();
        } else {
            // Non-Master Super Admin users cannot see Master Super Admin users
            $users = User::whereDoesntHave('roles', function($query) {
                $query->where('name', 'Master Super Admin');
            })->with(['roles', 'locations'])->get();
        }

        if ($users->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'message' => $users->map(function ($user) {
                    $role = $user->roles->first(); // Assume one role per user (or pick primary)
                    return [
                        'id' => $user->id,
                        'name_title' => $user->name_title,
                        'full_name' => $user->full_name,
                        'user_name' => $user->user_name,
                        'email' => $user->email,
                        'role' => $role?->name ?? '—',        // Display name
                        'role_key' => $role?->key ?? '—',     // Canonical key
                        'locations' => $user->locations->pluck('name')->toArray(),
                    ];
                }),
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Records Found!',
            ]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name_title' => 'required|string|max:10',
                'full_name' => 'required|string',
                'user_name' => 'required|string|max:50|unique:users,user_name',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:5|confirmed',
                'roles' => 'required|string|exists:roles,name',
                'location_id' => 'required|array',
                'location_id.*' => 'exists:locations,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $user = User::create([
            'name_title' => $request->name_title,
            'full_name' => $request->full_name,
            'user_name' => $request->user_name,
            'email' => $request->email,
            'role_name' => $request->roles,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole($request->roles);
        $user->locations()->sync($request->location_id);

        if ($user) {
            return response()->json([
                'status' => 200,
                'message' => "New User Details Created Successfully!"
            ]);
        } else {
            return response()->json([
                'status' => 500,
                'message' => "Something went wrong!"
            ]);
        }
    }

    public function show(int $id)
    {
        $user = User::with(['roles', 'locations'])->find($id);

        if ($user) {
            return response()->json([
                'status' => 200,
                'message' => [
                    'id' => $user->id,
                    'name_title' => $user->name_title,
                    'full_name' => $user->full_name,
                    'user_name' => $user->getRoleNames()->first(),
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'location_ids' => $user->locations->pluck('id')->toArray(),
                    'locations' => $user->locations->pluck('name')->toArray(),
                ]
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ]);
        }
    }

    public function edit(int $id)
    {
        $user = User::with(['roles', 'locations'])->find($id);

        if ($user) {
            return response()->json([
                'status' => 200,
                'message' => [
                    'id' => $user->id,
                    'name_title' => $user->name_title,
                    'full_name' => $user->full_name,
                    'user_name' => $user->user_name,
                    'email' => $user->email,
                    'role' => $user->getRoleNames()->first(),
                    'location_ids' => $user->locations->pluck('id')->toArray(),
                    'locations' => $user->locations->pluck('name')->toArray(),
                ]
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ]);
        }
    }

    public function update(Request $request, int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ]);
        }

        $validator = Validator::make(
            $request->all(),
            [
                'name_title' => 'required|string|max:10',
                'full_name' => 'required|string',
                'user_name' => 'required|string|max:50|unique:users,user_name,' . $user->id,
                'email' => 'required|email|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:5|confirmed',
                'roles' => 'required|string|exists:roles,name',
                'location_id' => 'required|array',
                'location_id.*' => 'exists:locations,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $data = [
            'name_title' => $request->name_title,
            'full_name' => $request->full_name,
            'user_name' => $request->user_name,
            'email' => $request->email,
        ];

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        if ($request->roles) {
            $user->syncRoles([$request->roles]);
        }

        $user->locations()->sync($request->location_id);

        return response()->json([
            'status' => 200,
            'message' => "User Details Updated Successfully!"
        ]);
    }

    public function destroy(int $id)
    {
        $currentUser = auth()->user();
        $userToDelete = User::find($id);

        if (!$userToDelete) {
            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ], 404);
        }

        // Prevent self-deletion
        if ($currentUser->id === $userToDelete->id) {
            return response()->json([
                'status' => 403,
                'message' => "You cannot delete your own account! This would cause system access issues."
            ], 403);
        }

        // Prevent deletion of Master Super Admin by non-Master Super Admin users
        $currentUserIsMasterSuperAdmin = $currentUser->roles->where('name', 'Master Super Admin')->count() > 0;
        $targetUserIsMasterSuperAdmin = $userToDelete->roles->where('name', 'Master Super Admin')->count() > 0;
        
        if ($targetUserIsMasterSuperAdmin && !$currentUserIsMasterSuperAdmin) {
            return response()->json([
                'status' => 403,
                'message' => "You do not have permission to delete Master Super Admin users."
            ], 403);
        }

        // Prevent deletion of the last Master Super Admin
        if ($targetUserIsMasterSuperAdmin) {
            $masterSuperAdminCount = User::whereHas('roles', function($query) {
                $query->where('name', 'Master Super Admin');
            })->count();
            
            if ($masterSuperAdminCount <= 1) {
                return response()->json([
                    'status' => 403,
                    'message' => "Cannot delete the last Master Super Admin user. This would make the system inaccessible."
                ], 403);
            }
        }

        // Proceed with deletion
        $userToDelete->roles()->detach();
        $userToDelete->locations()->detach();
        $userToDelete->delete();

        return response()->json([
            'status' => 200,
            'message' => "User Deleted Successfully!"
        ]);
    }
}
