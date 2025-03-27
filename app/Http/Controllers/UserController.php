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
        $roles = Role::all();
        $locations = Location::all();
        return view('user.user', compact('roles', 'locations'));
    }

    public function index()
    {
        $users = User::with(['roles', 'location'])->get();

        if ($users->count() > 0) {
            return response()->json([
                'status' => 200,
                'message' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name_title' => $user->name_title,
                        'full_name' => $user->full_name,
                        'user_name' => $user->user_name,
                        'email' => $user->email,
                        'role' => $user->getRoleNames()->first(),
                        'location' => $user->location->name,
                    ];
                }),
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Records Found!"
            ]);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name_title' => 'required|string|max:10',
            'full_name' => 'required|string',
            'user_name' => 'required|string|max:50|unique:users,user_name',
            'roles' => 'required|string|exists:roles,name',
            'location_id' => 'required|integer|exists:locations,id',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:5|confirmed',
        ]);

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
            'location_id' => $request->location_id,
            'password' => bcrypt($request->password),
        ]);

        $user->assignRole($request->roles);

        return response()->json([
            'status' => 200,
            'message' => "New User Created Successfully!"
        ]);
    }

    public function show(int $id)
    {
        $user = User::with(['roles', 'location'])->find($id);

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
                    'location_id' => $user->location->id,
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
        $user = User::with(['roles', 'location'])->find($id);

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
                    'location_id' => $user->location->id,
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

        $validator = Validator::make($request->all(), [
            'name_title' => 'required|string|max:10',
            'full_name' => 'required|string',
            'user_name' => 'required|string|max:50|unique:users,user_name,' . $user->id,
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:5|confirmed',
            'roles' => 'required|string|exists:roles,name',
            'location_id' => 'required|integer|exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        $user->update([
            'name_title' => $request->name_title,
            'full_name' => $request->full_name,
            'user_name' => $request->user_name,
            'email' => $request->email,
            'location_id' => $request->location_id,
            'password' => $request->filled('password') ? bcrypt($request->password) : $user->password,
        ]);

        $user->roles()->detach();
        $user->assignRole($request->roles);

        return response()->json([
            'status' => 200,
            'message' => "User Details Updated Successfully!"
        ]);
    }

    public function destroy(int $id)
    {
        $user = User::find($id);

        if ($user) {
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
