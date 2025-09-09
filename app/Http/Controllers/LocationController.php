<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\User;
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
        /** @var User $user */
        $user = Auth::user();

        if ($user->is_admin) {
            $locations = Location::with('parent', 'children')->get();
        } elseif ($this->isSalesRep($user)) {
            $locations = $this->getSalesRepAccessibleLocations($user);
        } else {
            $locations = $user->locations()->with('parent', 'children')->get();
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

    /**
     * Check if user has sales rep role
     */
    private function isSalesRep($user)
    {
        return $user->roles()->where('key', 'sales_rep')->exists();
    }

    /**
     * Get locations accessible to sales rep based on their assignments
     */
    private function getSalesRepAccessibleLocations($user)
    {
        // Get all active sales rep assignments for this user
        $salesRepAssignments = \App\Models\SalesRep::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['subLocation'])
            ->get();

        if ($salesRepAssignments->isEmpty()) {
            return collect();
        }

        // Get all sublocation IDs the sales rep is assigned to
        $assignedLocationIds = $salesRepAssignments
            ->pluck('subLocation.id')
            ->filter()
            ->unique()
            ->values();

        if ($assignedLocationIds->isEmpty()) {
            return collect();
        }

        // Return the assigned sublocations (vehicles)
        return Location::whereIn('id', $assignedLocationIds)
            ->with('parent', 'children')
            ->get();
    }

    private function getSalesRepLocations($user)
    {
        $vehicle = $user->vehicle;

        if (!$vehicle) {
            return collect(); // No vehicle → no access
        }

        if ($vehicle->vehicle_type === 'bike') {
            // Bike: Only main locations (no parent)
            return Location::whereNull('parent_id')->with('parent', 'children')->get();
        } else {
            // Van/Other: Only sub-locations (has parent) with vehicle details
            return Location::whereNotNull('parent_id')
                ->whereNotNull('vehicle_number')
                ->whereNotNull('vehicle_type')
                ->with('parent', 'children')
                ->get();
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
            'email' => 'required|email|max:255|unique:locations,email',
            'mobile' => ['required', 'regex:/^(0?\d{9,10})$/'],
            'telephone_no' => ['required', 'regex:/^(0?\d{9,10})$/'],
            'logo_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
            'vehicle_number' => [
                'nullable',
                'string',
                'max:50',
                'unique:locations,vehicle_number',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->parent_id && empty($value)) {
                        $fail('Vehicle number is required for sublocations.');
                    }
                    if (!$request->parent_id && !empty($value)) {
                        $fail('Parent locations should not have vehicle details.');
                    }
                }
            ],
            'vehicle_type' => [
                'nullable',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($request) {
                    if ($request->parent_id && empty($value)) {
                        $fail('Vehicle type is required for sublocations.');
                    }
                    if (!$request->parent_id && !empty($value)) {
                        $fail('Parent locations should not have vehicle details.');
                    }
                }
            ],
        ], [
            'mobile.required' => 'Please enter a valid mobile number with 10 digits.',
            'mobile.regex' => 'Mobile must be 9 or 10 digits.',
            'telephone_no.required' => 'Please enter a valid telephone number with 10 digits.',
            'telephone_no.regex' => 'Telephone must be 9 or 10 digits.',
            'location_id.regex' => 'Location ID must be in format LOC0001.',
            'location_id.unique' => 'This Location ID is already taken.',
            'vehicle_number.unique' => 'This vehicle number is already in use.',
            'logo_image.image' => 'The logo must be an image file.',
            'logo_image.mimes' => 'The logo must be a file of type: jpeg, jpg, png, gif.',
            'logo_image.max' => 'The logo must not be greater than 2MB.',
        ]);

        // Auto-generate location_id if not provided
        $location_id = $request->location_id;
        if (!$location_id) {
            $location_id = $this->generateLocationId();
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Handle logo image upload
        $logoImagePath = null;
        if ($request->hasFile('logo_image')) {
            $file = $request->file('logo_image');
            $filename = time() . '_' . $location_id . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/location_logos'), $filename);
            $logoImagePath = 'storage/location_logos/' . $filename;
        }

        try {
            $locationData = [
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
                'logo_image' => $logoImagePath,
            ];

            // Add vehicle details if this is a sublocation
            if ($request->parent_id) {
                $locationData['vehicle_number'] = $request->vehicle_number;
                $locationData['vehicle_type'] = $request->vehicle_type;
            }

            $location = Location::create($locationData);

            /** @var User $authUser */
            $authUser = Auth::user();
            if ($authUser->is_admin && method_exists($authUser, 'locations')) {
                $authUser->locations()->attach($location->id);
            }

            // Load relationships for response
            $location->load('parent', 'children');

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
        $location = Location::with('parent', 'children')->find($id);
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
                    function ($attribute, $value, $fail) use ($location) {
                        if ($value && Location::find($value)?->parent_id !== null) {
                            $fail('Only main locations can be parents.');
                        }
                        // Prevent setting self as parent
                        if ($value == $location->id) {
                            $fail('A location cannot be its own parent.');
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
                'vehicle_number' => [
                    'nullable',
                    'string',
                    'max:50',
                    'unique:locations,vehicle_number,' . $id,
                    function ($attribute, $value, $fail) use ($request) {
                        // If parent_id is set (sublocation), vehicle_number is required
                        if ($request->parent_id && empty($value)) {
                            $fail('Vehicle number is required for sublocations.');
                        }
                        // If parent_id is null (parent location), vehicle_number should be null
                        if (!$request->parent_id && !empty($value)) {
                            $fail('Parent locations should not have vehicle details.');
                        }
                    }
                ],
                'vehicle_type' => [
                    'nullable',
                    'string',
                    'max:50',
                    function ($attribute, $value, $fail) use ($request) {
                        // If parent_id is set (sublocation), vehicle_type is required
                        if ($request->parent_id && empty($value)) {
                            $fail('Vehicle type is required for sublocations.');
                        }
                        // If parent_id is null (parent location), vehicle_type should be null
                        if (!$request->parent_id && !empty($value)) {
                            $fail('Parent locations should not have vehicle details.');
                        }
                    }
                ],
            ], [
                'mobile.required' => 'Mobile number is required.',
                'mobile.regex' => 'Mobile must be 9 or 10 digits.',
                'location_id.regex' => 'Location ID must be in format LOC0001.',
                'location_id.unique' => 'This Location ID is already taken.',
                'vehicle_number.unique' => 'This vehicle number is already in use.',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

                // Handle logo image upload
                $logoImagePath = $location->logo_image; // Keep existing image by default
                if ($request->hasFile('logo_image')) {
                    // Delete old image if it exists
                    if ($location->logo_image && file_exists(public_path($location->logo_image))) {
                        unlink(public_path($location->logo_image));
                    }
                    
                    $file = $request->file('logo_image');
                    $filename = time() . '_' . $location->location_id . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('storage/location_logos'), $filename);
                    $logoImagePath = 'storage/location_logos/' . $filename;
                }

                $location->update([

                    'name' => $request->name,
                    'location_id' => $request->location_id,
                    'address' => $request->address,
                    'province' => $request->province,
                    'district' => $request->district,
                    'city' => $request->city,
                    'email' => $request->email,
                    'mobile' => $request->mobile,
                    'telephone_no' => $request->telephone_no,
                    'logo_image' => $logoImagePath,

                ]);
                return response()->json([
                    'status' => 200,
                    'message' => "Old Location  Details Updated Successfully!"
                ]);
            try {
                $updateData = $validator->validated();
                
                // Handle vehicle details based on parent_id
                if (!$request->parent_id) {
                    // Parent location - remove vehicle details
                    $updateData['vehicle_number'] = null;
                    $updateData['vehicle_type'] = null;
                }
                
                $location->update($updateData);
                
                // Load relationships for response
                $location->load('parent', 'children');

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


    public function getParentLocations()
    {
        $locations = Location::whereNull('parent_id')
            ->with('children')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Parent locations retrieved successfully.',
            'data' => $locations,
        ]);
    }

    /**
     * Get all sublocations for a specific parent
     */
    public function getSublocations($parentId)
    {
        $parent = Location::find($parentId);
        if (!$parent) {
            return response()->json([
                'status' => false,
                'message' => 'Parent location not found.',
            ], 404);
        }

        if ($parent->parent_id !== null) {
            return response()->json([
                'status' => false,
                'message' => 'This is not a parent location.',
            ], 400);
        }

        $sublocations = Location::where('parent_id', $parentId)
            ->with('parent')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Sublocations retrieved successfully.',
            'data' => $sublocations,
        ]);
    }

    /**
     * Get locations by vehicle type
     */
    public function getLocationsByVehicleType($vehicleType)
    {
        $locations = Location::where('vehicle_type', $vehicleType)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->get();

        return response()->json([
            'status' => true,
            'message' => "Locations with vehicle type '{$vehicleType}' retrieved successfully.",
            'data' => $locations,
        ]);
    }

    /**
     * Search locations by vehicle number
     */
    public function searchByVehicleNumber(Request $request)
    {
        $vehicleNumber = $request->query('vehicle_number');
        
        if (!$vehicleNumber) {
            return response()->json([
                'status' => false,
                'message' => 'Vehicle number parameter is required.',
            ], 400);
        }

        $location = Location::where('vehicle_number', $vehicleNumber)
            ->with('parent', 'children')
            ->first();

        if (!$location) {
            return response()->json([
                'status' => false,
                'message' => 'No location found with this vehicle number.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Location found.',
            'data' => $location,
        ]);
    }

    /**
     * Get location hierarchy (parent with all children)
     */
    public function getLocationHierarchy($id)
    {
        $location = Location::with('parent', 'children')->find($id);
        
        if (!$location) {
            return response()->json([
                'status' => false,
                'message' => 'Location not found.',
            ], 404);
        }

        // If this is a sublocation, get the parent and all its children
        if ($location->parent_id) {
            $hierarchy = Location::with('children')
                ->find($location->parent_id);
        } else {
            // If this is a parent, get it with all children
            $hierarchy = $location;
        }

        return response()->json([
            'status' => true,
            'message' => 'Location hierarchy retrieved successfully.',
            'data' => $hierarchy,
        ]);
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
