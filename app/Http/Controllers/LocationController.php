<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view location', ['only' => ['index', 'show', 'location']]);
        $this->middleware('permission:create location', ['only' => ['store']]);
        $this->middleware('permission:edit location', ['only' => ['edit', 'update']]);
        $this->middleware('permission:delete location', ['only' => ['destroy']]);
    }

    public function location()
    {
        return view('location.location');
    }

    /**
     * Display a listing of locations based on user role and vehicle type.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->is_admin) {
            $locations = Location::with('parent')->get();
        } elseif ($user->role === 'Sales Rep') {
            $locations = $this->getSalesRepLocations($user);
        } else {
            $locations = $user->locations()->with('parent')->get();
        }

        if ($locations->isNotEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Locations retrieved successfully.',
                'data' => $locations,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No locations found.',
            'data' => [],
        ], 404);
    }

    private function getSalesRepLocations($user)
    {
        $vehicle = $user->vehicle;

        if (!$vehicle) {
            return collect(); // No vehicle → no access
        }

        if ($vehicle->vehicle_type === 'bike') {
            // Bike: Only main locations (no parent)
            return Location::whereNull('parent_id')->with('parent')->get();
        } else {
            // Van/Other: Only sub-locations (has parent)
            return Location::whereNotNull('parent_id')->with('parent')->get();
        }
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($request) {
                    $query = Location::where('name', $value);
                    if ($request->parent_id) {
                        $query->where('parent_id', $request->parent_id);
                    } else {
                        $query->whereNull('parent_id');
                    }
                    if ($query->exists()) {
                        $fail('A location with this name already exists in the selected context.');
                    }
                }
            ],
            'parent_id' => [
                'nullable',
                'exists:locations,id',
                function ($attribute, $value, $fail) {
                    if ($value && Location::find($value)?->parent_id !== null) {
                        $fail('Sub-locations cannot have children. Only main locations can be parents.');
                    }
                }
            ],
            'location_id' => [
                'nullable',
                'string',
                'unique:locations,location_id',
                'regex:/^LOC\d{4}$/',
            ],
            'address' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => ['required', 'regex:/^(0?\d{9,10})$/'],
        ], [
            'mobile.required' => 'Mobile number is required.',
            'mobile.regex' => 'Mobile must be 9 or 10 digits.',
            'location_id.regex' => 'Location ID must be in format LOC0001.',
            'location_id.unique' => 'This Location ID is already taken.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $location_id = $request->location_id ?? $this->generateLocationId();

        try {
            $location = Location::create([
                'name' => $request->name,
                'location_id' => $location_id,
                'parent_id' => $request->parent_id,
                'address' => $request->address,
                'province' => $request->province,
                'district' => $request->district,
                'city' => $request->city,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'telephone_no' => $request->telephone_no,
            ]);

            if (Auth::user()->is_admin) {
                Auth::user()->locations()->attach($location->id);
            }

            return response()->json([
                'status' => true,
                'message' => 'Location created successfully.',
                'data' => $location,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to create location.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function generateLocationId()
    {
        $prefix = 'LOC';
        $latest = Location::where('location_id', 'like', $prefix . '%')
            ->orderBy('location_id', 'desc')
            ->first();

        $number = $latest ? (int) substr($latest->location_id, 3) + 1 : 1;
        $newId = $prefix . sprintf('%04d', $number);

        while (Location::where('location_id', $newId)->exists()) {
            $number++;
            $newId = $prefix . sprintf('%04d', $number);
        }

        return $newId;
    }

    public function show($id)
    {
        $location = Location::with('parent')->find($id);
        if (!$location) {
            return response()->json([
                'status' => false,
                'message' => 'Location not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Location retrieved.',
            'data' => $location,
        ]);
    }

    public function edit($id)
    {
        return $this->show($id);
    }

    public function update(Request $request, $id)
    {
        $location = Location::find($id);
        if (!$location) {
            return response()->json([
                'status' => false,
                'message' => 'Location not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($request, $location) {
                    $query = Location::where('name', $value)->where('id', '!=', $location->id);
                    if ($request->parent_id) {
                        $query->where('parent_id', $request->parent_id);
                    } else {
                        $query->whereNull('parent_id');
                    }
                    if ($query->exists()) {
                        $fail('Duplicate name in same context.');
                    }
                }
            ],
            'parent_id' => [
                'nullable',
                'exists:locations,id',
                function ($attribute, $value, $fail) {
                    if ($value && Location::find($value)?->parent_id !== null) {
                        $fail('Only main locations can be parents.');
                    }
                }
            ],
            'location_id' => [
                'required',
                'string',
                'regex:/^LOC\d{4}$/',
                'unique:locations,location_id,' . $id,
            ],
            'address' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'mobile' => ['required', 'regex:/^(0?\d{9,10})$/'],

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $location->update($validator->validated());

            return response()->json([
                'status' => true,
                'message' => 'Location updated successfully.',
                'data' => $location,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Update failed.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $location = Location::find($id);
        if (!$location) {
            return response()->json([
                'status' => false,
                'message' => 'Location not found.',
            ], 404);
        }

        $location->delete();

        return response()->json([
            'status' => true,
            'message' => 'Location deleted successfully.',
        ]);
    }
}
