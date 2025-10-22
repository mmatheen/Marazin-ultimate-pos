<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
     * Display a listing of locations based on user role and permissions.
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        // Master Super Admin can see all locations
        if ($this->isMasterSuperAdmin($user)) {
            $locations = Location::with('parent', 'children')->orderBy('id', 'asc')->get();
        }
        // Users with override location scope permission can see all locations
        elseif ($this->hasLocationBypassPermission($user)) {
            $locations = Location::with('parent', 'children')->orderBy('id', 'asc')->get();
        }
        // Sales Rep gets their accessible locations
        elseif ($this->isSalesRep($user)) {
            $locations = $this->getSalesRepAccessibleLocations($user);
        } 
        // Regular users (including regular admin) see only their assigned locations
        else {
            $locations = $user->locations()->with('parent', 'children')->orderBy('id', 'asc')->get();
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
            ->orderBy('id', 'asc')
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
            return Location::whereNull('parent_id')->with('parent', 'children')->orderBy('id', 'asc')->get();
        } else {
            // Van/Other: Only sub-locations (has parent) with vehicle details
            return Location::whereNotNull('parent_id')
                ->whereNotNull('vehicle_number')
                ->whereNotNull('vehicle_type')
                ->with('parent', 'children')
                ->orderBy('id', 'asc')
                ->get();
        }
    }

    /**
     * Store a newly created location.
     */
    public function store(Request $request)
    {
        // Auto-generate location_id if not provided
        if (!$request->location_id) {
            $request->merge(['location_id' => $this->generateLocationId()]);
        }

        $isSubLocation = !empty($request->parent_id);
        
        // Build validation rules based on location type
        $validationRules = [
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
            ]
        ];

        if ($isSubLocation) {
            // For sublocations - only vehicle details are required, others inherited from parent
            $validationRules += [
                'vehicle_number' => [
                    'required',
                    'string',
                    'max:50',
                    'unique:locations,vehicle_number',
                    'regex:/^[A-Z0-9\-]+$/i'
                ],
                'vehicle_type' => [
                    'required',
                    'string',
                    'in:Van,Truck,Bike,Car,Three Wheeler,Lorry,Other'
                ],
            ];
        } else {
            // For main locations - all contact and address details are required
            $validationRules += [
                'address' => 'required|string|max:255',
                'province' => 'required|string|max:255',
                'district' => 'required|string|max:255',
                'city' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:locations,email',
                'mobile' => ['nullable', 'regex:/^(0?\d{9,10})$/'],
                'logo_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
                'invoice_layout_pos' => 'required|string|in:80mm,a4,dot_matrix',
                // Ensure no vehicle details for main locations
                'vehicle_number' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if (!empty($value)) {
                            $fail('Main locations should not have vehicle details.');
                        }
                    }
                ],
                'vehicle_type' => [
                    'nullable',
                    function ($attribute, $value, $fail) {
                        if (!empty($value)) {
                            $fail('Main locations should not have vehicle details.');
                        }
                    }
                ],
            ];
        }

        $customMessages = [
            'mobile.regex' => 'Mobile must be 9 or 10 digits.',
            'location_id.regex' => 'Location ID must be in format LOC0001.',
            'location_id.unique' => 'This Location ID is already taken.',
            'vehicle_number.unique' => 'This vehicle number is already in use.',
            'vehicle_number.regex' => 'Vehicle number format is invalid. Use letters, numbers, and hyphens only.',
            'vehicle_number.required' => 'Vehicle number is required for sublocations.',
            'vehicle_type.required' => 'Vehicle type is required for sublocations.',
            'vehicle_type.in' => 'Please select a valid vehicle type.',
            'logo_image.image' => 'The logo must be an image file.',
            'logo_image.mimes' => 'The logo must be a file of type: jpeg, jpg, png, gif.',
            'logo_image.max' => 'The logo must not be greater than 2MB.',
        ];

        $validator = Validator::make($request->all(), $validationRules, $customMessages);

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
            $filename = time() . '_' . $request->location_id . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/location_logos'), $filename);
            $logoImagePath = 'storage/location_logos/' . $filename;
        }

        try {
            $locationData = [
                'name' => $request->name,
                'location_id' => $request->location_id,
                'parent_id' => $request->parent_id,
            ];

            if ($isSubLocation) {
                // For sublocations - inherit parent details and add vehicle info
                $parentLocation = Location::find($request->parent_id);
                
                $locationData += [
                    'address' => $parentLocation->address,
                    'province' => $parentLocation->province,
                    'district' => $parentLocation->district,
                    'city' => $parentLocation->city,
                    'email' => $parentLocation->email,
                    'mobile' => $parentLocation->mobile,
                    'telephone_no' => $parentLocation->telephone_no,
                    'logo_image' => $parentLocation->logo_image,
                    'vehicle_number' => $request->vehicle_number,
                    'vehicle_type' => $request->vehicle_type,
                    'invoice_layout_pos' => $request->invoice_layout_pos ?? $parentLocation->invoice_layout_pos ?? '80mm',
                ];
            } else {
                // For main locations - use provided details
                $locationData += [
                    'address' => $request->address,
                    'province' => $request->province,
                    'district' => $request->district,
                    'city' => $request->city ?? '',
                    'email' => $request->email ?? '',
                    'mobile' => $request->mobile ?? 0,
                    'telephone_no' => $request->telephone_no,
                    'logo_image' => $logoImagePath,
                    'invoice_layout_pos' => $request->invoice_layout_pos ?? '80mm',
                ];
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

            // Preserve existing location_id during update if not provided in request
            if (!$request->location_id) {
                $request->merge(['location_id' => $location->location_id]);
                Log::info('Location update: Preserving existing location_id', [
                    'location_id' => $location->location_id,
                    'location_name' => $location->name,
                    'updated_by' => auth()->id()
                ]);
            } else {
                Log::info('Location update: Using provided location_id', [
                    'old_location_id' => $location->location_id,
                    'new_location_id' => $request->location_id,
                    'location_name' => $location->name,
                    'updated_by' => auth()->id()
                ]);
            }

            $isSubLocation = !empty($request->parent_id);
        
            // Build validation rules based on location type (same as store method)
            $validationRules = [
                'name' => [
                    'required',
                    'string',
                    'max:255',
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
                    'nullable',
                    'string',
                    'regex:/^LOC\d{4}$/',
                    'unique:locations,location_id,' . $id,
                ]
            ];

            if ($isSubLocation) {
                // For sublocations - only vehicle details are required, others inherited from parent
                $validationRules += [
                    'vehicle_number' => [
                        'required',
                        'string',
                        'max:50',
                        'unique:locations,vehicle_number,' . $id,
                        'regex:/^[A-Z0-9\-]+$/i'
                    ],
                    'vehicle_type' => [
                        'required',
                        'string',
                        'in:Van,Truck,Bike,Car,Three Wheeler,Lorry,Other'
                    ],
                ];
            } else {
                // For main locations - all contact and address details are required
                $validationRules += [
                    'address' => 'required|string|max:255',
                    'province' => 'required|string|max:255',
                    'district' => 'required|string|max:255',
                    'city' => 'nullable|string|max:255',
                    'email' => [
                        'nullable',
                        'email',
                        'max:255',
                        function ($attribute, $value, $fail) use ($id, $request) {
                            if ($value) {
                                $location = Location::find($id);
                                
                                // Check if email exists in other locations
                                $emailExists = Location::where('email', $value)
                                    ->where('id', '!=', $id)
                                    ->where(function($query) use ($location) {
                                        // Allow same email for parent-child relationships
                                        $query->where('parent_id', '!=', $location->id)
                                              ->where('parent_id', '!=', $location->parent_id)
                                              ->where('id', '!=', $location->parent_id);
                                    })
                                    ->exists();
                                
                                if ($emailExists) {
                                    $fail('The email has already been taken by another location.');
                                }
                            }
                        }
                    ],
                    'mobile' => ['nullable', 'regex:/^(0?\d{9,10})$/'],
                    'logo_image' => 'nullable|image|mimes:jpeg,jpg,png,gif|max:2048',
                    'invoice_layout_pos' => 'required|string|in:80mm,a4,dot_matrix',
                    // Ensure no vehicle details for main locations
                    'vehicle_number' => [
                        'nullable',
                        function ($attribute, $value, $fail) {
                            if (!empty($value)) {
                                $fail('Main locations should not have vehicle details.');
                            }
                        }
                    ],
                    'vehicle_type' => [
                        'nullable',
                        function ($attribute, $value, $fail) {
                            if (!empty($value)) {
                                $fail('Main locations should not have vehicle details.');
                            }
                        }
                    ],
                ];
            }

            $customMessages = [
                'mobile.regex' => 'Mobile must be 9 or 10 digits.',
                'location_id.regex' => 'Location ID must be in format LOC0001.',
                'location_id.unique' => 'This Location ID is already taken.',
                'vehicle_number.unique' => 'This vehicle number is already in use.',
                'vehicle_number.regex' => 'Vehicle number format is invalid. Use letters, numbers, and hyphens only.',
                'vehicle_number.required' => 'Vehicle number is required for sublocations.',
                'vehicle_type.required' => 'Vehicle type is required for sublocations.',
                'vehicle_type.in' => 'Please select a valid vehicle type.',
                'logo_image.image' => 'The logo must be an image file.',
                'logo_image.mimes' => 'The logo must be a file of type: jpeg, jpg, png, gif.',
                'logo_image.max' => 'The logo must not be greater than 2MB.',
            ];


            

            $validator = Validator::make($request->all(), $validationRules, $customMessages);

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

            try {
                $updateData = [
                    'name' => $request->name,
                    'location_id' => $request->location_id,
                    'parent_id' => $request->parent_id,
                ];

                if ($isSubLocation) {
                    // For sublocations - inherit parent details and update vehicle info
                    $parentLocation = Location::find($request->parent_id);
                    
                    $updateData += [
                        'address' => $parentLocation->address,
                        'province' => $parentLocation->province,
                        'district' => $parentLocation->district,
                        'city' => $parentLocation->city,
                        'email' => $parentLocation->email,
                        'mobile' => $parentLocation->mobile,
                        'telephone_no' => $parentLocation->telephone_no,
                        'logo_image' => $parentLocation->logo_image,
                        'vehicle_number' => $request->vehicle_number,
                        'vehicle_type' => $request->vehicle_type,
                        'invoice_layout_pos' => $request->invoice_layout_pos ?? $parentLocation->invoice_layout_pos ?? '80mm',
                    ];
                } else {
                    // For main locations - use provided details
                    $updateData += [
                        'address' => $request->address,
                        'province' => $request->province,
                        'district' => $request->district,
                        'city' => $request->city ?? '',
                        'email' => $request->email ?? '',
                        'mobile' => $request->mobile ?? 0,
                        'telephone_no' => $request->telephone_no,
                        'logo_image' => $logoImagePath,
                        'vehicle_number' => null,
                        'vehicle_type' => null,
                        'invoice_layout_pos' => $request->invoice_layout_pos ?? '80mm',
                    ];
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

    /**
     * Check if user is Master Super Admin
     */
    private function isMasterSuperAdmin($user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return $user->roles->pluck('name')->contains('Master Super Admin') || 
               $user->roles->pluck('key')->contains('master_super_admin');
    }

    /**
     * Check if user has location bypass permission
     */
    private function hasLocationBypassPermission($user): bool
    {
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        // Check if any role has bypass_location_scope flag
        foreach ($user->roles as $role) {
            if ($role->bypass_location_scope ?? false) {
                return true;
            }
        }

        // Check for specific permissions
        return $user->hasPermissionTo('override location scope');
    }
}
