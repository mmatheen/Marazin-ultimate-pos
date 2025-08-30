<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesRep;
use App\Models\Route;
use App\Models\User;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesRepController extends Controller
{
    /**
     * Display a listing of sales representatives with grouped routes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $salesReps = SalesRep::with([
                'user:id,user_name,full_name,email',
                'subLocation:id,name,parent_id,vehicle_number,vehicle_type',
                'subLocation.parent:id,name,vehicle_number,vehicle_type',
                'route:id,name,status',
            ])->get();

            if ($salesReps->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No sales representatives found.',
                    'data' => [],
                ], 404);
            }

            $formattedReps = $salesReps->map(function($rep) {
                $subLocation = $rep->subLocation;
                $parentLocation = $subLocation?->parent;
                return array_merge(
                    $this->formatSalesRep($rep),
                    [
                        'sub_location_vehicle_number' => $subLocation?->vehicle_number,
                        'sub_location_vehicle_type' => $subLocation?->vehicle_type,
                        'parent_location_vehicle_number' => $parentLocation?->vehicle_number,
                        'parent_location_vehicle_type' => $parentLocation?->vehicle_type,
                    ]
                );
            });

            return response()->json([
                'status' => true,
                'message' => 'Sales representatives retrieved successfully.',
                'data' => $formattedReps,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve sales representatives.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store new route assignments for a sales rep.
     */
    public function store(Request $request)
    {
        // Check if this is a bulk assignment (new format) or single assignment (legacy format)
        if ($request->has('assignments')) {
            return $this->storeBulkAssignments($request);
        } else {
            return $this->storeSingleAssignment($request);
        }
    }

    /**
     * Store bulk route assignments for a sales rep (new format).
     */
    private function storeBulkAssignments(Request $request)
    {
        // Pre-process the request data to handle can_sell conversion
        $requestData = $request->all();
        if (isset($requestData['assignments'])) {
            foreach ($requestData['assignments'] as $index => $assignment) {
                // Convert can_sell to boolean if it exists
                if (isset($assignment['can_sell'])) {
                    $requestData['assignments'][$index]['can_sell'] = filter_var($assignment['can_sell'], FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        $validator = Validator::make($requestData, [
            'user_id' => 'required|exists:users,id',
            'assignments' => 'required|array|min:1',
            'assignments.*.sub_location_id' => 'required|exists:locations,id',
            'assignments.*.route_ids' => 'required|array|min:1',
            'assignments.*.route_ids.*' => 'exists:routes,id',
            'assignments.*.assigned_date' => 'nullable|date',
            'assignments.*.end_date' => 'nullable|date|after_or_equal:assignments.*.assigned_date',
            'assignments.*.status' => 'nullable|in:active,inactive',
            'assignments.*.can_sell' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get the user and validate they exist
        $user = User::with(['locations', 'roles'])->find($requestData['user_id']);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
                'errors' => ['user_id' => ['The selected user does not exist.']],
            ], 422);
        }

        // Check if user has sales rep role
        $isSalesRep = $this->validateSalesRepRole($user);
        if (!$isSalesRep) {
            return response()->json([
                'status' => false,
                'message' => 'User must have sales rep role.',
                'errors' => ['user_id' => ['The selected user is not a sales representative.']],
            ], 422);
        }

        // Get current user (the one making the request) for permission checking
        $currentUser = auth()->user();
        $isAdminOrManager = $currentUser && ($currentUser->is_admin || $currentUser->roles->whereIn('name', ['admin', 'manager'])->count() > 0);

        // Validate locations and prepare for auto-assignment if needed
        $locationsToAssign = [];
        $userLocationIds = $user->locations->pluck('id')->toArray();
        
        foreach ($requestData['assignments'] as $index => $assignment) {
            $subLocationId = $assignment['sub_location_id'];
            
            // Validate that the sub-location is actually a sub-location (has parent)
            $subLocation = Location::with('parent')->find($subLocationId);
            if (!$subLocation || !$subLocation->parent_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Selected location must be a sub-location.',
                    'errors' => ["assignments.{$index}.sub_location_id" => ['Please select a valid sub-location.']],
                ], 422);
            }

            // Check if user has access to this location
            if (!in_array($subLocationId, $userLocationIds)) {
                // If admin/manager, automatically assign the location to the user
                if ($isAdminOrManager) {
                    $locationsToAssign[] = $subLocationId;
                    // Also assign the parent location for consistency
                    if ($subLocation->parent_id && !in_array($subLocation->parent_id, $userLocationIds) && !in_array($subLocation->parent_id, $locationsToAssign)) {
                        $locationsToAssign[] = $subLocation->parent_id;
                    }
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'User does not have access to one or more selected locations.',
                        'errors' => ["assignments.{$index}.sub_location_id" => ['User is not assigned to this location.']],
                    ], 422);
                }
            }
        }

        DB::beginTransaction();
        try {
            // Auto-assign locations if needed (admin/manager privilege)
            if (!empty($locationsToAssign)) {
                $user->locations()->syncWithoutDetaching($locationsToAssign);
                // Log this action
                Log::info("Auto-assigned locations to user {$user->id} by admin/manager {$currentUser->id}", [
                    'user_id' => $user->id,
                    'location_ids' => $locationsToAssign,
                    'assigned_by' => $currentUser->id
                ]);
            }

            $createdAssignments = [];
            $duplicates = [];
            $errors = [];

            foreach ($requestData['assignments'] as $assignment) {
                foreach ($assignment['route_ids'] as $routeId) {
                    // Check for existing active assignment
                    $existingAssignment = SalesRep::where('user_id', $requestData['user_id'])
                        ->where('sub_location_id', $assignment['sub_location_id'])
                        ->where('route_id', $routeId)
                        ->where('status', 'active')
                        ->whereNull('end_date')
                        ->first();

                    if ($existingAssignment) {
                        $route = Route::find($routeId);
                        $location = Location::find($assignment['sub_location_id']);
                        $duplicates[] = "Route '{$route->name}' at '{$location->name}'";
                        continue;
                    }

                    try {
                        $salesRep = SalesRep::create([
                            'user_id' => $requestData['user_id'],
                            'sub_location_id' => $assignment['sub_location_id'],
                            'route_id' => $routeId,
                            'assigned_date' => $assignment['assigned_date'] ?? now(),
                            'end_date' => $assignment['end_date'] ?? null,
                            'status' => $assignment['status'] ?? 'active',
                            'can_sell' => $assignment['can_sell'] ?? true,
                        ]);

                        $createdAssignments[] = $salesRep;
                    } catch (\Exception $e) {
                        $route = Route::find($routeId);
                        $location = Location::find($assignment['sub_location_id']);
                        $errors[] = "Failed to assign route '{$route->name}' at '{$location->name}': " . $e->getMessage();
                    }
                }
            }

            DB::commit();

            $message = count($createdAssignments) . ' assignment(s) created successfully.';
            if (!empty($duplicates)) {
                $message .= ' Skipped duplicates: ' . implode(', ', $duplicates);
            }
            if (!empty($errors)) {
                $message .= ' Errors: ' . implode(', ', $errors);
            }
            if (!empty($locationsToAssign)) {
                $message .= ' User was automatically assigned to ' . count($locationsToAssign) . ' new location(s).';
            }

            // Load relationships for response
            $formattedAssignments = collect($createdAssignments)->map(function($rep) {
                return $this->formatSalesRep($rep->load(['user', 'subLocation.parent', 'route']));
            });

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => $formattedAssignments,
                'created_count' => count($createdAssignments),
                'duplicate_count' => count($duplicates),
                'error_count' => count($errors),
                'auto_assigned_locations' => count($locationsToAssign),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to assign sales rep.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a single route assignment for a sales rep (legacy format).
     */
    private function storeSingleAssignment(Request $request)
    {
        // Pre-process the request data to handle can_sell conversion
        $requestData = $request->all();
        if (isset($requestData['can_sell'])) {
            $requestData['can_sell'] = filter_var($requestData['can_sell'], FILTER_VALIDATE_BOOLEAN);
        }

        $validator = Validator::make($requestData, [
            'user_id' => 'required|exists:users,id',
            'sub_location_id' => 'required|exists:locations,id',
            'route_id' => 'required|exists:routes,id',
            'assigned_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:assigned_date',
            'status' => 'nullable|in:active,inactive',
            'can_sell' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get the user and validate they exist
        $user = User::with(['locations', 'roles'])->find($requestData['user_id']);
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
                'errors' => ['user_id' => ['The selected user does not exist.']],
            ], 422);
        }

        // Check if user has sales rep role
        $isSalesRep = $this->validateSalesRepRole($user);
        if (!$isSalesRep) {
            return response()->json([
                'status' => false,
                'message' => 'User must have sales rep role.',
                'errors' => ['user_id' => ['The selected user is not a sales representative.']],
            ], 422);
        }

        // Get current user (the one making the request) for permission checking
        $currentUser = auth()->user();
        $isAdminOrManager = $currentUser && ($currentUser->is_admin || $currentUser->roles->whereIn('name', ['admin', 'manager'])->count() > 0);

        // Validate that the sub-location is actually a sub-location (has parent)
        $subLocation = Location::with('parent')->find($requestData['sub_location_id']);
        if (!$subLocation || !$subLocation->parent_id) {
            return response()->json([
                'status' => false,
                'message' => 'Selected location must be a sub-location.',
                'errors' => ['sub_location_id' => ['Please select a valid sub-location.']],
            ], 422);
        }

        // Check if user has access to the selected sub-location
        $userLocationIds = $user->locations->pluck('id')->toArray();
        $hasAccess = in_array($requestData['sub_location_id'], $userLocationIds);
        
        if (!$hasAccess) {
            // If admin/manager, automatically assign the location to the user
            if ($isAdminOrManager) {
                $locationsToAssign = [$requestData['sub_location_id']];
                // Also assign the parent location for consistency
                if ($subLocation->parent_id && !in_array($subLocation->parent_id, $userLocationIds)) {
                    $locationsToAssign[] = $subLocation->parent_id;
                }
                $user->locations()->syncWithoutDetaching($locationsToAssign);
                
                // Log this action
                Log::info("Auto-assigned locations to user {$user->id} by admin/manager {$currentUser->id}", [
                    'user_id' => $user->id,
                    'location_ids' => $locationsToAssign,
                    'assigned_by' => $currentUser->id
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User does not have access to the selected location.',
                    'errors' => ['sub_location_id' => ['User is not assigned to this location.']],
                ], 422);
            }
        }

        // Check for existing active assignment
        $existingAssignment = SalesRep::where('user_id', $requestData['user_id'])
            ->where('sub_location_id', $requestData['sub_location_id'])
            ->where('route_id', $requestData['route_id'])
            ->where('status', 'active')
            ->whereNull('end_date')
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'status' => false,
                'message' => 'User is already assigned to this location and route.',
                'errors' => ['combination' => ['Active assignment already exists.']],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $salesRep = SalesRep::create([
                'user_id' => $requestData['user_id'],
                'sub_location_id' => $requestData['sub_location_id'],
                'route_id' => $requestData['route_id'],
                'assigned_date' => $requestData['assigned_date'] ?? now(),
                'end_date' => $requestData['end_date'],
                'status' => $requestData['status'] ?? 'active',
                'can_sell' => $requestData['can_sell'] ?? true,
            ]);

            DB::commit();

            $message = 'Sales rep assigned successfully.';
            if (!$hasAccess && $isAdminOrManager) {
                $message .= ' User was automatically assigned to the required location(s).';
            }

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => $this->formatSalesRep($salesRep->load(['user', 'subLocation.parent', 'route'])),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to assign sales rep.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a single route assignment.
     */
    public function show($id)
    {
        try {
            $salesRep = SalesRep::with([
                'user:id,user_name,full_name,email',
                'subLocation:id,name,parent_id',
                'subLocation.parent:id,name',
                'route:id,name,status',
            ])->find($id);

            if (!$salesRep) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sales rep assignment not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Assignment retrieved successfully.',
                'data' => $this->formatSalesRep($salesRep),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve assignment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a route assignment.
     */
    public function update(Request $request, $id)
    {
        $salesRep = SalesRep::find($id);
        if (!$salesRep) {
            return response()->json([
                'status' => false,
                'message' => 'Route assignment not found.',
            ], 404);
        }

        // Pre-process the request data to handle can_sell conversion
        $requestData = $request->all();
        if (isset($requestData['can_sell'])) {
            $requestData['can_sell'] = filter_var($requestData['can_sell'], FILTER_VALIDATE_BOOLEAN);
        }

        $validator = Validator::make($requestData, [
            'user_id' => 'required|exists:users,id',
            'sub_location_id' => 'required|exists:locations,id',
            'route_id' => 'required|exists:routes,id',
            'assigned_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:assigned_date',
            'status' => 'nullable|in:active,inactive',
            'can_sell' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Validate that user has access to the selected sub-location
        $user = User::with('locations')->find($requestData['user_id']);
        $hasAccess = $user->locations->contains('id', $requestData['sub_location_id']);
        
        if (!$hasAccess) {
            return response()->json([
                'status' => false,
                'message' => 'User does not have access to the selected location.',
                'errors' => ['sub_location_id' => ['User is not assigned to this location.']],
            ], 422);
        }

        // Validate that the sub-location is actually a sub-location (has parent)
        $subLocation = Location::find($requestData['sub_location_id']);
        if (!$subLocation || !$subLocation->parent_id) {
            return response()->json([
                'status' => false,
                'message' => 'Selected location must be a sub-location.',
                'errors' => ['sub_location_id' => ['Please select a valid sub-location.']],
            ], 422);
        }

        // Check for existing active assignment (excluding current record)
        $existingAssignment = SalesRep::where('user_id', $requestData['user_id'])
            ->where('sub_location_id', $requestData['sub_location_id'])
            ->where('route_id', $requestData['route_id'])
            ->where('status', 'active')
            ->where('id', '!=', $id)
            ->whereNull('end_date')
            ->first();

        if ($existingAssignment) {
            return response()->json([
                'status' => false,
                'message' => 'User is already assigned to this location and route.',
                'errors' => ['combination' => ['Active assignment already exists.']],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $salesRep->update([
                'user_id' => $requestData['user_id'],
                'sub_location_id' => $requestData['sub_location_id'],
                'route_id' => $requestData['route_id'],
                'assigned_date' => $requestData['assigned_date'] ?? $salesRep->assigned_date,
                'end_date' => $requestData['end_date'],
                'status' => $requestData['status'] ?? $salesRep->status,
                'can_sell' => $requestData['can_sell'] ?? $salesRep->can_sell,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Route assignment updated successfully.',
                'data' => $this->formatSalesRep($salesRep->refresh()->load(['user', 'subLocation.parent', 'route'])),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update assignment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a route assignment.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $salesRep = SalesRep::with('user')->find($id);
            if (!$salesRep) {
                return response()->json([
                    'status' => false,
                    'message' => 'Assignment not found.',
                ], 404);
            }

            $userName = $salesRep->user?->user_name ?? 'Unknown';

            $salesRep->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => "Route assignment for '{$userName}' deleted successfully.",
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete assignment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available routes
     */
    public function getAvailableRoutes()
    {
        $routes = Route::withCount('cities')
            ->where('status', 'active')
            ->get(['id', 'name'])
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'name' => $r->name,
                    'cities_count' => $r->cities_count,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Available routes retrieved successfully.',
            'data' => $routes,
        ], 200);
    }

    /**
     * Get users with sales rep role and their accessible locations
     */
    public function getAvailableUsers()
    {
        try {
            $salesRepUsers = User::with(['locations' => function($query) {
                $query->whereNotNull('parent_id'); // Only sub-locations
                $query->with('parent:id,name');
            }, 'roles'])
            ->whereHas('roles', function($query) {
                $query->where('name', 'Sales Rep')
                      ->orWhere('name', 'sales rep')
                      ->orWhere('key', 'sales_rep');
            })
            ->get(['id', 'user_name', 'full_name', 'email'])
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'user_name' => $user->user_name,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'role_key' => $user->roles->first()->key ?? null, // Add role_key for frontend
                    'accessible_locations' => $user->locations->map(function($location) {
                        return [
                            'id' => $location->id,
                            'name' => $location->name,
                            'parent_name' => $location->parent?->name,
                            'full_name' => ($location->parent?->name ? $location->parent->name . ' → ' : '') . $location->name,
                        ];
                    }),
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Sales rep users retrieved successfully.',
                'data' => $salesRepUsers,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve users.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign a user to specific locations (Admin/Manager only)
     */
    public function assignUserToLocations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'location_ids' => 'required|array|min:1',
            'location_ids.*' => 'exists:locations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check permissions
        $currentUser = auth()->user();
        $isAdminOrManager = $currentUser && ($currentUser->is_admin || $currentUser->roles->whereIn('name', ['admin', 'manager'])->count() > 0);
        
        if (!$isAdminOrManager) {
            return response()->json([
                'status' => false,
                'message' => 'Insufficient permissions. Only admin or manager can assign locations to users.',
            ], 403);
        }

        try {
            $user = User::with(['locations', 'roles'])->find($request->user_id);
            
            // Check if user has sales rep role
            $isSalesRep = $this->validateSalesRepRole($user);
            if (!$isSalesRep) {
                return response()->json([
                    'status' => false,
                    'message' => 'User must have sales rep role to be assigned to locations.',
                ], 422);
            }

            // Assign locations (merge with existing)
            $user->locations()->syncWithoutDetaching($request->location_ids);
            
            Log::info("Admin/Manager assigned locations to user", [
                'user_id' => $user->id,
                'location_ids' => $request->location_ids,
                'assigned_by' => $currentUser->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'User assigned to locations successfully.',
                'data' => [
                    'user_id' => $user->id,
                    'assigned_locations' => count($request->location_ids),
                    'total_locations' => $user->locations()->count(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to assign user to locations.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function formatSalesRep($rep)
    {
        return [
            'id' => $rep->id,
            'user_id' => $rep->user_id,
            'sub_location_id' => $rep->sub_location_id,
            'route_id' => $rep->route_id,
            'assigned_date' => $rep->assigned_date,
            'end_date' => $rep->end_date,
            'status' => $rep->status,
            'can_sell' => $rep->can_sell,
            'user' => $rep->user ? [
                'id' => $rep->user->id,
                'user_name' => $rep->user->user_name,
                'full_name' => $rep->user->full_name,
                'email' => $rep->user->email,
            ] : null,
            'sub_location' => $rep->subLocation ? [
                'id' => $rep->subLocation->id,
                'name' => $rep->subLocation->name,
                'parent_name' => $rep->subLocation->parent?->name,
                'full_name' => ($rep->subLocation->parent?->name ? $rep->subLocation->parent->name . ' → ' : '') . $rep->subLocation->name,
            ] : null,
            'route' => $rep->route ? [
                'id' => $rep->route->id,
                'name' => $rep->route->name,
                'status' => $rep->route->status,
            ] : null,
            'created_at' => $rep->created_at,
            'updated_at' => $rep->updated_at,
        ];
    }

    /**
     * Check if user has sales rep role (supports both name and key)
     */
    private function validateSalesRepRole($user)
    {
        return $user->roles->contains(function($role) {
            return $role->name === 'Sales Rep' || 
                   $role->name === 'sales rep' || 
                   $role->key === 'sales_rep';
        });
    }
}

