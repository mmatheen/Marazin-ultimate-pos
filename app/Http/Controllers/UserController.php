<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function user()
    {
        $roles = Role::all();
        $locations = Location::all();
        return view('user.user', compact('roles', 'locations'));
    }

    public function index()
    {
        $getValue = User::with('location')->get();
        if ($getValue->count() > 0) {

            // get the role name code start
            $getValue->transform(function ($user) {
                $roleNames = $user->roles->pluck('name')->toArray(); // Fetch the role names as an array
                $user->role_name = implode(', ', $roleNames); // Join role names into a single string
                unset($user->roles); // Remove the roles array from the response
                return $user;
            });
            // get the role name code end

            return response()->json([
                'status' => 200,
                'message' => $getValue
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
        //  dd($request->all());
        $validator = Validator::make(
            $request->all(),
            [
                'name_title' => 'required|string|max:10',
                'name' => 'required|string',
                'user_name' => 'required|string|max:50|unique:users,user_name',
                'roles' => 'required|string|max:50',
                'location_id' => 'required|integer|exists:locations,id',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:5|confirmed',  // Requires at least 5 characters and confirms password
                'roles' => 'required|string|exists:roles,name'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = User::create([

                'name_title' => $request->name_title ?? '',
                'name' => $request->name ?? '',
                'user_name' => $request->user_name ?? '',
                'role_name' => $request->roles ?? '',
                'location_id' => $request->location_id ?? '',
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            // Assign role by role to model_has_roles table code start
            if ($getValue && $request->roles) {
                $getValue->assignRole($request->roles);
            }
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
        $getValue = User::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
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

        
        $getValue = User::with('location')->find($id);

        if ($getValue) {

            // get the role name code start
            $roleNames = $getValue->roles->pluck('name')->toArray(); // Fetch the role names as an array
            $getValue->role_name = implode(', ', $roleNames); // Join role names into a single string
            unset($getValue->roles); // Remove the roles array from the response
            // get the role name code end

            return response()->json([
                'status' => 200,
                'message' => $getValue
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
        // Fetch the user by ID
        $userDetails = User::find($id);

        // Check if the user exists
        if (!$userDetails) {
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
                'name' => 'required|string',
                'user_name' => 'required|string|max:50|unique:users,user_name,' . $userDetails->id, // Unique username except for current user
                'roles' => 'required|string|max:50',
                'location_id' => 'required|integer|exists:locations,id',
                'email' => 'required|email|unique:users,email,' . $userDetails->id, // Unique email except for current user
                'password' => 'nullable|string|min:5|confirmed', // Allow null for password
                'roles' => 'required|string|exists:roles,name'
            ]
        );

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        }

        // Update the user details
        // Detach and assign roles if roles are provided code start for modal_has_roles
        if ($request->roles) {
            $userDetails->roles()->detach();
            $userDetails->assignRole($request->roles);
        }

        $userDetails->update([
            'name_title' => $request->name_title ?? '',
            'name' => $request->name ?? '',
            'user_name' => $request->user_name ?? '',
            'role_name' => $request->roles ?? '',
            'location_id' => $request->location_id ?? '',
            'email' => $request->email,
            'password' => $request->filled('password') ? bcrypt($request->password) : $userDetails->password, // Update password only if provided
        ]);

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
        $getValue = User::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "User Details Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such User Found!"
            ]);
        }
    }
}
