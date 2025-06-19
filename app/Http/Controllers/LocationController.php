<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{

    function __construct()
    {
        $this->middleware('permission:view location', ['only' => ['index', 'show','location']]);
        $this->middleware('permission:create location', ['only' => ['store']]);
        $this->middleware('permission:edit location', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete location', ['only' => ['destroy']]);
    }

    public function location()
    {
        return view('location.location');
    }

   // Index method to list locations
   public function index()
   {
       // Check if the user is a Super Admin
       if (Auth::user()->is_admin) {
           // Super Admin can see all locations
           $locations = Location::all();
       } else {
           // Non-admin users can see only their assigned locations
           $locations = Auth::user()->locations;
       }

       if ($locations->count() > 0) {
           return response()->json([
               'status' => 200,
               'message' => $locations,
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



    public function store(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|unique:locations',
                'location_id' => [
                    'nullable',
                    'string',
                    'max:255',
                    'unique:locations',
                    function ($attribute, $value, $fail) {
                        if (!preg_match('/^LOC\d{4}$/', $value)) {
                            $fail('The ' . $attribute . ' must be in the format LOC followed by 4 digits. eg: LOC0001');
                        }
                    }
                ],
                'address' => 'required|string|max:255|unique:locations',
                'province' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'email' => 'required|email|unique:locations',
                'mobile' => ['required', 'regex:/^(0?\d{9})$/'],
                'telephone_no' => ['required', 'regex:/^(0?\d{9})$/'],
            ],
            [
                'mobile.required' => 'Please enter a valid mobile number with 10 digits.',
                'telephone_no.required' => 'Please enter a valid telephone number with 10 digits.',
                'location_id.unique' => 'The location_id has already been taken.',
            ]
        );

        // Auto-generate location_id if not provided
        $location_id = $request->location_id;
        if (!$location_id) {
            $prefix = 'LOC';
            $latestLocation = Location::where('location_id', 'like', $prefix . '%')->orderBy('location_id', 'desc')->first();

            if ($latestLocation) {
                $latestID = intval(substr($latestLocation->location_id, strlen($prefix)));
            } else {
                $latestID = 1;
            }

            $nextID = $latestID + 1;
            $location_id = $prefix . sprintf("%04d", $nextID);

            // Ensure the generated location_id is unique
            while (Location::where('location_id', $location_id)->exists()) {
                $nextID++;
                $location_id = $prefix . sprintf("%04d", $nextID);
            }
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            // Create the location and assign to Super Admin
            $location = Location::create([
                'name' => $request->name,
                'location_id' => $location_id,
                'address' => $request->address,
                'province' => $request->province,
                'district' => $request->district,
                'city' => $request->city,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'telephone_no' => $request->telephone_no,
            ]);

            if ($location) {
                // Automatically assign the location to the Super Admin
                if (Auth::user()->is_admin) {
                    Auth::user()->locations()->attach($location->id);
                }

                return response()->json([
                    'status' => 200,
                    'message' => "New Location Created Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => "Something went wrong!"
                ]);
            }
        }
    }



    public function show(int $id)
    {
        $getValue = Location::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Location Found!"
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
        $getValue = Location::find($id);
        if ($getValue) {
            return response()->json([
                'status' => 200,
                'message' => $getValue
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Such Location Found!"
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

                'name' => 'required|string',  // Add max length if applicable
                'address' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'email' => 'required|email',
                'mobile' => ['required', 'regex:/^(0?\d{9})$/'],  // Matches 10 digits with or without leading 0
                'telephone_no' => ['required', 'regex:/^(0?\d{9})$/'],  // Matches 10 digits with or without leading 0
            ],

            [
                'mobile.required' => 'Please enter a valid mobile number with 10 digits.',
                'telephone_no.required' => 'Please enter a valid telephone number with 10 digits.',
                'mobile.regex' => 'Please enter a valid mobile number with 10 digits.',
                'telephone_no.regex' => 'Please enter a valid telephone number with 10 digits.',
            ]
        );


        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'errors' => $validator->messages()
            ]);
        } else {
            $getValue = Location::find($id);

            if ($getValue) {
                $getValue->update([

                    'name' => $request->name,
                    'location_id' => $request->location_id,
                    'address' => $request->address,
                    'province' => $request->province,
                    'district' => $request->district,
                    'city' => $request->city,
                    'email' => $request->email,
                    'mobile' => $request->mobile,
                    'telephone_no' => $request->telephone_no,

                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Location  Details Updated Successfully!"
                ]);
            } else {
                return response()->json([
                    'status' => 404,
                    'message' => "No Such Location Found!"
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
        $getValue = Location::find($id);
        if ($getValue) {

            $getValue->delete();
            return response()->json([
                'status' => 200,
                'message' => "Location Details Deleted Successfully!"
            ]);
        } else {

            return response()->json([
                'status' => 404,
                'message' => "No Such Location Found!"
            ]);
        }
    }
}
