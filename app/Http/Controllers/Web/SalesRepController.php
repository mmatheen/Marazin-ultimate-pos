<?php

namespace App\Http\Controllers\Web;

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
     * Display the sales representatives listing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('sales_rep_module.sales_reps.index');
    }

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
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving sales representatives: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving sales representatives: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created sales representative.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => [
                    'required',
                    'integer',
                    'exists:users,id',
                    function ($attribute, $value, $fail) {
                        if (SalesRep::where('user_id', $value)->exists()) {
                            $fail('This user is already assigned as a sales representative.');
                        }
                    },
                ],
                'sub_location_id' => 'required|integer|exists:locations,id',
                'route_id' => 'required|integer|exists:routes,id',
                'assigned_date' => 'required|date',
                'end_date' => 'nullable|date|after:assigned_date',
                'can_sell' => 'required|boolean',
                'status' => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $salesRep = SalesRep::create($request->all());

            if (!$salesRep) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to create sales representative.',
                ], 500);
            }

            $salesRep->load([
                'user:id,user_name,full_name,email',
                'subLocation:id,name,parent_id,vehicle_number,vehicle_type',
                'route:id,name,status',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales representative created successfully.',
                'data' => $this->formatSalesRep($salesRep),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating sales representative: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error creating sales representative: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified sales representative.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $salesRep = SalesRep::with([
                'user:id,user_name,full_name,email',
                'subLocation:id,name,parent_id,vehicle_number,vehicle_type',
                'route:id,name,status',
            ])->find($id);

            if (!$salesRep) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sales representative not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Sales representative retrieved successfully.',
                'data' => $this->formatSalesRep($salesRep),
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving sales representative: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving sales representative: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified sales representative.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $salesRep = SalesRep::find($id);

            if (!$salesRep) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sales representative not found.',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'user_id' => [
                    'required',
                    'integer',
                    'exists:users,id',
                    function ($attribute, $value, $fail) use ($id) {
                        if (SalesRep::where('user_id', $value)->where('id', '!=', $id)->exists()) {
                            $fail('This user is already assigned as a sales representative.');
                        }
                    },
                ],
                'sub_location_id' => 'required|integer|exists:locations,id',
                'route_id' => 'required|integer|exists:routes,id',
                'assigned_date' => 'required|date',
                'end_date' => 'nullable|date|after:assigned_date',
                'can_sell' => 'required|boolean',
                'status' => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $updated = $salesRep->update($request->all());

            if (!$updated) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to update sales representative.',
                ], 500);
            }

            $salesRep->load([
                'user:id,user_name,full_name,email',
                'subLocation:id,name,parent_id,vehicle_number,vehicle_type',
                'route:id,name,status',
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales representative updated successfully.',
                'data' => $this->formatSalesRep($salesRep),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating sales representative: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error updating sales representative: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified sales representative.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $salesRep = SalesRep::find($id);

            if (!$salesRep) {
                return response()->json([
                    'status' => false,
                    'message' => 'Sales representative not found.',
                ], 404);
            }

            DB::beginTransaction();

            $deleted = $salesRep->delete();

            if (!$deleted) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to delete sales representative.',
                ], 500);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales representative deleted successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting sales representative: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error deleting sales representative: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available routes for assignment.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableRoutes()
    {
        try {
            $routes = Route::where('status', 'active')
                ->with('cities:id,name,district,province')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Available routes retrieved successfully.',
                'data' => $routes,
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving available routes: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving available routes: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get users available for sales rep assignment.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableUsers()
    {
        try {
            $assignedUserIds = SalesRep::pluck('user_id')->toArray();
            
            $availableUsers = User::whereNotIn('id', $assignedUserIds)
                ->select('id', 'user_name', 'full_name', 'email')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Available users retrieved successfully.',
                'data' => $availableUsers,
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving available users: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving available users: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Assign user to multiple locations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignUserToLocations(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer|exists:users,id',
                'assignments' => 'required|array|min:1',
                'assignments.*.sub_location_id' => 'required|integer|exists:locations,id',
                'assignments.*.route_id' => 'required|integer|exists:routes,id',
                'assignments.*.assigned_date' => 'required|date',
                'assignments.*.end_date' => 'nullable|date|after:assignments.*.assigned_date',
                'assignments.*.can_sell' => 'required|boolean',
                'assignments.*.status' => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            $createdAssignments = [];

            foreach ($request->assignments as $assignment) {
                $assignmentData = array_merge($assignment, ['user_id' => $request->user_id]);
                
                $salesRep = SalesRep::create($assignmentData);
                
                if ($salesRep) {
                    $salesRep->load([
                        'user:id,user_name,full_name,email',
                        'subLocation:id,name,parent_id,vehicle_number,vehicle_type',
                        'route:id,name,status',
                    ]);
                    
                    $createdAssignments[] = $this->formatSalesRep($salesRep);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'User assigned to locations successfully.',
                'data' => $createdAssignments,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning user to locations: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error assigning user to locations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current user's sales rep assignments (for POS).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMyAssignments()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not authenticated.',
                ], 401);
            }

            $assignments = SalesRep::where('user_id', $user->id)
                ->where('status', 'active')
                ->with([
                    'subLocation:id,name,parent_id,vehicle_number,vehicle_type',
                    'subLocation.parent:id,name,vehicle_number,vehicle_type',
                    'route:id,name,status',
                    'route.cities:id,name,district,province'
                ])
                ->get();

            if ($assignments->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'No active assignments found for this user.',
                ], 403);
            }

            $formattedAssignments = $assignments->map(function($assignment) {
                return [
                    'id' => $assignment->id,
                    'sub_location' => $assignment->subLocation,
                    'route' => $assignment->route,
                    'assigned_date' => $assignment->assigned_date,
                    'end_date' => $assignment->end_date,
                    'can_sell' => $assignment->can_sell,
                    'status' => $assignment->status,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Sales rep assignments retrieved successfully.',
                'data' => $formattedAssignments,
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving user assignments: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving assignments: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Format sales rep data for consistent response structure.
     *
     * @param  \App\Models\SalesRep  $salesRep
     * @return array
     */
    private function formatSalesRep($salesRep)
    {
        return [
            'id' => $salesRep->id,
            'user' => $salesRep->user,
            'sub_location' => $salesRep->subLocation,
            'route' => $salesRep->route,
            'assigned_date' => $salesRep->assigned_date,
            'end_date' => $salesRep->end_date,
            'can_sell' => $salesRep->can_sell,
            'status' => $salesRep->status,
            'created_at' => $salesRep->created_at,
            'updated_at' => $salesRep->updated_at,
        ];
    }
}
