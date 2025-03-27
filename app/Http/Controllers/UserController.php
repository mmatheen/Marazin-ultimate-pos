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
        $this->middleware('permission:view user', ['only' => ['index', 'show','user']]);
        $this->middleware('permission:create user', ['only' => ['store']]);
        $this->middleware('permission:edit user', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete user', ['only' => ['destroy']]);
    }

    public function user()
    {
        return view('user.user');
    }

    public function index()
    {
        $users = User::with(['roles','location'])->get();  // it will get the roles details from spatie model not custom model

        if ($users->isNotEmpty()) {
            return response()->json([
                'status' => 200,
                'message' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name_title' => $user->name_title,
                        'full_name' => $user->full_name,
                        'user_name' => $user->user_name,
                        'email' => $user->email,
                        'role' => $user->getRoleNames()->first(), // Convert array to a single string
                        'location' => $user->location->name,
                    ];
                }),
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'No Records Found!'
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
        //  dd($request->all());
        $validator = Validator::make(
            $request->all(),
            [
                'name_title' => 'required|string|max:10',
                'full_name' => 'required|string',
                'user_name' => 'required|string|max:50|unique:users,user_name',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:5|confirmed',  // Requires at least 5 characters and confirms password
                'roles' => 'required|string|exists:roles,name',
                'location_id' => 'required|string|exists:locations,id',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = User::create([

                'name_title' => $request->name_title,
                'full_name' => $request->full_name,
                'user_name' => $request->user_name,
                'email' => $request->email,
                'location_id' => $request->location_id,
                'password' => bcrypt($request->password),
            ]);

            // Assign role by role to model_has_roles table code start
            $getValue->assignRole($request->roles);
            // Assign role by role to model_has_roles table code end


            if ($getValue) {
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
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        // Find the user by ID
        $user = User::with(['roles','location'])->find($id);

        if ($user) {
            // Get the first role name as a string
            $roleName = $user->getRoleNames()->first();

            return response()->json([
                'status' => 200,
                'message' => [
                    'id' => $user->id,
                    'name_title' => $user->name_title,
                    'full_name' => $user->full_name,
                    'user_name' => $user->user_name,
                    'email' => $user->email,
                    'role' => $roleName, // Single role name instead of an array
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */

    public function edit(int $id)
    {
        // Find the user by ID
        $user = User::with(['roles','location'])->find($id);

        if ($user) {
            // Get the first role name as a string
            $roleName = $user->getRoleNames()->first();

            return response()->json([
                'status' => 200,
                'message' => [
                    'id' => $user->id,
                    'name_title' => $user->name_title,
                    'full_name' => $user->full_name,
                    'user_name' => $user->user_name,
                    'email' => $user->email,
                    'role' => $roleName, // Single role name instead of an array
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


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        // Find the user by ID
        $user = User::find($id);

        // Check if the user exists
        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ]);
        }

        // Validate incoming request data
        $validator = Validator::make(
            $request->all(),
            [
                'name_title' => 'required|string|max:10',
                'full_name' => 'required|string',
                'user_name' => 'required|string|max:50|unique:users,user_name,' . $user->id, // Unique except for current user
                'email' => 'required|email|unique:users,email,' . $user->id, // Unique except for current user
                'password' => 'nullable|string|min:5|confirmed', // Allow null for password
                'roles' => 'required|string|exists:roles,name',// Role should exist in roles table
                'location_id' => 'required|string|exists:locations,id',
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        // Update user details
        $user->update([
            'name_title' => $request->name_title,
            'full_name' => $request->full_name,
            'user_name' => $request->user_name,
            'email' => $request->email,
            'location_id' => $request->location_id,
            'password' => $request->filled('password') ? bcrypt($request->password) : $user->password, // Update password only if provided
        ]);

        // **Update Role (Detach old and assign new role)**
        if ($request->roles) {
            $user->syncRoles([$request->roles]); // Remove old roles & assign new one
        }

        return response()->json([
            'status' => 200,
            'message' => "User Details Updated Successfully!"
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
        // Find the user by ID
        $user = User::find($id);

        if ($user) {
            // Remove all assigned roles before deleting
            $user->roles()->detach();

            // Delete user
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

