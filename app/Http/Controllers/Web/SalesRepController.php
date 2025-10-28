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
            // First, update all statuses based on current dates
            SalesRep::where('status', '!=', SalesRep::STATUS_CANCELLED)
                ->get()
                ->each(fn($assignment) => $assignment->updateStatusByDate());

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
                'status' => 'nullable|in:active,expired,upcoming,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Create assignment with automatic status calculation
            $assignmentData = $request->all();
            
            // If no status provided, calculate it based on dates
            if (!isset($assignmentData['status']) || empty($assignmentData['status'])) {
                $tempAssignment = new SalesRep($assignmentData);
                $assignmentData['status'] = $tempAssignment->getCalculatedStatus();
            }

            $salesRep = SalesRep::create($assignmentData);

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
                'status' => 'nullable|in:active,expired,upcoming,cancelled',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            DB::beginTransaction();

            // Update assignment with automatic status calculation if needed
            $updateData = $request->all();
            
            // If no status provided or dates changed, recalculate status
            if (!isset($updateData['status']) || empty($updateData['status']) || 
                ($request->has('assigned_date') || $request->has('end_date'))) {
                
                // Temporarily update the model to calculate status
                $tempAssignment = clone $salesRep;
                $tempAssignment->fill($updateData);
                $updateData['status'] = $tempAssignment->getCalculatedStatus();
            }

            $updated = $salesRep->update($updateData);

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
     * Update all assignments status based on their dates
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateAllStatusesByDate()
    {
        try {
            $updated = 0;
            
            // Get all assignments except cancelled ones
            $assignments = SalesRep::where('status', '!=', SalesRep::STATUS_CANCELLED)->get();
            
            foreach ($assignments as $assignment) {
                if ($assignment->updateStatusByDate()) {
                    $updated++;
                }
            }

            Log::info("Web Status update completed", [
                'total_checked' => $assignments->count(),
                'updated_count' => $updated,
            ]);

            return response()->json([
                'status' => true,
                'message' => "Status update completed. {$updated} assignments updated.",
                'data' => [
                    'total_checked' => $assignments->count(),
                    'updated_count' => $updated,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("Web Status update error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update assignment statuses.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get assignments that are expiring soon
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getExpiringSoon(Request $request)
    {
        try {
            $days = $request->get('days', 3); // Default 3 days

            $assignments = SalesRep::with([
                'user:id,user_name,full_name,email',
                'subLocation:id,name,vehicle_number,vehicle_type',
                'route:id,name,status',
            ])
            ->where('status', SalesRep::STATUS_ACTIVE)
            ->whereNotNull('end_date')
            ->whereRaw('DATEDIFF(end_date, CURDATE()) BETWEEN 0 AND ?', [$days])
            ->orderBy('end_date')
            ->get();

            $formattedAssignments = $assignments->map(function($assignment) {
                return array_merge(
                    $this->formatSalesRep($assignment),
                    [
                        'days_until_expiry' => $assignment->getDaysUntilExpiry(),
                        'is_expiring_soon' => $assignment->isExpiringSoon(),
                    ]
                );
            });

            return response()->json([
                'status' => true,
                'message' => 'Expiring assignments retrieved successfully.',
                'data' => $formattedAssignments,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Web Get expiring assignments error", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve expiring assignments.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get assignment statistics by status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatusStatistics()
    {
        try {
            $stats = [
                'active' => SalesRep::where('status', SalesRep::STATUS_ACTIVE)->count(),
                'expired' => SalesRep::where('status', SalesRep::STATUS_EXPIRED)->count(),
                'upcoming' => SalesRep::where('status', SalesRep::STATUS_UPCOMING)->count(),
                'cancelled' => SalesRep::where('status', SalesRep::STATUS_CANCELLED)->count(),
                'expiring_soon' => SalesRep::where('status', SalesRep::STATUS_ACTIVE)
                    ->whereNotNull('end_date')
                    ->whereRaw('DATEDIFF(end_date, CURDATE()) BETWEEN 0 AND 3')
                    ->count(),
            ];

            $stats['total'] = array_sum(array_values($stats)) - $stats['expiring_soon']; // Don't double count expiring

            return response()->json([
                'status' => true,
                'message' => 'Status statistics retrieved successfully.',
                'data' => $stats,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Web Get status statistics error", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve status statistics.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel an assignment (set status to cancelled)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelAssignment($id)
    {
        try {
            $assignment = SalesRep::find($id);

            if (!$assignment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Assignment not found.',
                ], 404);
            }

            if ($assignment->status === SalesRep::STATUS_CANCELLED) {
                return response()->json([
                    'status' => false,
                    'message' => 'Assignment is already cancelled.',
                ], 400);
            }

            $assignment->status = SalesRep::STATUS_CANCELLED;
            $assignment->save();

            Log::info("Web Assignment cancelled", [
                'assignment_id' => $id,
                'user_id' => $assignment->user_id,
                'cancelled_by' => auth()->id(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Assignment cancelled successfully.',
                'data' => $this->formatSalesRep($assignment),
            ], 200);

        } catch (\Exception $e) {
            Log::error("Web Cancel assignment error", [
                'assignment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to cancel assignment.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reactivate a cancelled assignment
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reactivateAssignment($id)
    {
        try {
            $assignment = SalesRep::find($id);

            if (!$assignment) {
                return response()->json([
                    'status' => false,
                    'message' => 'Assignment not found.',
                ], 404);
            }

            if ($assignment->status !== SalesRep::STATUS_CANCELLED) {
                return response()->json([
                    'status' => false,
                    'message' => 'Only cancelled assignments can be reactivated.',
                ], 400);
            }

            // Update status based on dates
            $assignment->updateStatusByDate();

            Log::info("Web Assignment reactivated", [
                'assignment_id' => $id,
                'user_id' => $assignment->user_id,
                'new_status' => $assignment->status,
                'reactivated_by' => auth()->id(),
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Assignment reactivated successfully.',
                'data' => $this->formatSalesRep($assignment),
            ], 200);

        } catch (\Exception $e) {
            Log::error("Web Reactivate assignment error", [
                'assignment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to reactivate assignment.',
                'error' => $e->getMessage(),
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
