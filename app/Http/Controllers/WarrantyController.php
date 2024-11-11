<?php

namespace App\Http\Controllers;

use App\Models\Warranty;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class WarrantyController extends Controller
{


        // public function __construct()
        // {
        //     $this->middleware('permission:View Warranty', ['only' => ['index', 'warranty']]);
        //     $this->middleware('permission:Add Warranty', ['only' => ['store']]);
        //     $this->middleware('permission:Edit/Update Warranty', ['only' => ['update', 'edit']]);
        //     $this->middleware('permission:Delete Warranty', ['only' => ['destroy']]);
        // }

        function __construct()
        {
             $this->middleware('permission:View Warranty|Add Warranty|Edit/Update Warranty|Delete Warranty',['only' => ['index','store']]);
             $this->middleware('permission:View Warranty', ['only' => ['index','warranty']]);
             $this->middleware('permission:Add Warranty', ['only' => ['store']]);
             $this->middleware('permission:Edit/Update Warranty', ['only' => ['edit','update']]);
             $this->middleware('permission:Delete Warranty', ['only' => ['destroy']]);
        }

    public function warranty()
    {
        return view('warranty.warranty');
    }

    public function index()
    {
        $getValue = Warranty::all();

        //it will getting the login role and geting permission for role code start
        $role = Auth::user()->role_name;
        $adminRole = Role::findByName($role); // Or 'Super Admin', 'Manager', 'Cashier' as appropriate
        $permissions=$adminRole->permissions->pluck('name');
        //it will getting the login role and geting permission for role code end

        // $getValue = Warranty::withTrashed()->get(); // it will get all record with soft deleted records also
        if ($getValue->count() > 0) {

            return response()->json([
                'status' => 200,
                'message' => $getValue,
                'permissions' => $permissions
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string',
                'duration' => 'required|integer',
                'duration_type' => 'required|string',

            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {

            $getValue = Warranty::create([
                'name' => $request->name,
                'duration' => $request->duration,
                'duration_type' => $request->duration_type,
                'description' => $request->description ?? '',
            ]);


            if ($getValue) {
                return response()->json([
                    'status' => 200,
                    'message' => "New Warranty Details Created Successfully!"
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
        $getValue = Warranty::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Warranty Found!"
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
        $getValue = Warranty::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Warranty Found!"
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
        $validator = Validator::make(
            $request->all(),
            [

                'name' => 'required|string',
                'duration' => 'required|integer',
                'duration_type' => 'required|string',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = Warranty::find($id);

            if ($getValue) {
                $getValue->update([

                    'name' => $request->name,
                    'duration' => $request->duration,
                    'duration_type' => $request->duration_type,
                    'description' => $request->description,

                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Warranty  Details Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Warranty Found!"
                ]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lecturer  $lecturer
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $getValue = Warranty::find($id);
        if ($getValue) {

            $getValue->delete(); //this delete function will only work for soft delete when i use soft delete colomn in migration and modal
            // $getValue->forceDelete(); //this delete function will only work for Foce delete when i use force delete it will permantly delete from database
            return response()->json([
                'status' => 200,
                'message' => "Warranty Details Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Warranty Found!"
            ]);
        }
    }
}
