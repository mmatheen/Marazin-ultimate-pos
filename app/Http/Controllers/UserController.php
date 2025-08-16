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
        $users = User::with(['roles', 'locations'])->get();

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
        $user = User::find($id);

        if ($user) {
            $user->roles()->detach();
            $user->locations()->detach();
            $user->delete();

            return response()->json([
                'status' => 200,
                'message' => "User Deleted Successfully!"
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ]);
        }
    }
}
