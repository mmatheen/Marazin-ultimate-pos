<?php

namespace App\Http\Controllers;

use App\Models\SalesRep;
use App\Models\VehicleLocation;
use App\Models\Route;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesRepController extends Controller
{
    /**
     * Display a listing of sales representatives.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('sales_reps.index');
    }

    /**
     * Get DataTable data for sales representatives.
     *
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function getData()
    {
        $salesReps = SalesRep::with([
            'user:id,user_name,email',
            'vehicleLocation:id,vehicle_id,location_id',
            'vehicleLocation.vehicle:id,vehicle_number,vehicle_type',
            'vehicleLocation.location:id,name',
            'route:id,name',
            'route.cities:id,name'
        ])->select([
            'id',
            'user_id',
            'vehicle_location_id',
            'route_id',
            'assigned_date',
            'status',
            'created_at'
        ]);

        return DataTables::of($salesReps)
            ->addColumn('user_name', function ($salesRep) {
                return $salesRep->user->user_name ?? 'N/A';
            })
            ->addColumn('user_email', function ($salesRep) {
                return $salesRep->user->email ?? 'N/A';
            })
            ->addColumn('vehicle_details', function ($salesRep) {
                $vehicle = $salesRep->vehicleLocation->vehicle ?? null;
                return $vehicle ? $vehicle->vehicle_number . ' (' . $vehicle->vehicle_type . ')' : 'N/A';
            })
            ->addColumn('location_name', function ($salesRep) {
                return $salesRep->vehicleLocation->location->name ?? 'N/A';
            })
            ->addColumn('route_name', function ($salesRep) {
                return $salesRep->route->name ?? 'N/A';
            })
            ->addColumn('route_cities', function ($salesRep) {
                if ($salesRep->route && $salesRep->route->cities->count() > 0) {
                    $cities = $salesRep->route->cities->pluck('name')->take(3);
                    $citiesText = $cities->implode(', ');

                    if ($salesRep->route->cities->count() > 3) {
                        $citiesText .= ' +' . ($salesRep->route->cities->count() - 3) . ' more';
                    }

                    return '<span class="badge badge-info">' . $citiesText . '</span>';
                }
                return '<span class="badge badge-secondary">No cities</span>';
            })
            ->addColumn('status_badge', function ($salesRep) {
                if ($salesRep->status === 'active') {
                    return '<span class="badge badge-success">Active</span>';
                } else {
                    return '<span class="badge badge-danger">Inactive</span>';
                }
            })
            ->addColumn('assigned_date_formatted', function ($salesRep) {
                return $salesRep->assigned_date ? $salesRep->assigned_date->format('M d, Y') : 'N/A';
            })
            ->addColumn('action', function ($salesRep) {
                $editUrl = route('sales-reps.edit', $salesRep->id);
                $deleteUrl = route('sales-reps.destroy', $salesRep->id);

                return '
                    <div class="btn-group" role="group">
                        <a href="' . route('sales-reps.show', $salesRep->id) . '" class="btn btn-sm btn-info" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="' . $editUrl . '" class="btn btn-sm btn-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-sm btn-danger delete-btn" data-id="' . $salesRep->id . '" data-url="' . $deleteUrl . '" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['route_cities', 'status_badge', 'action'])
            ->make(true);
    }

    /**
     * Show the form for creating a new sales representative.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $users = User::select('id', 'user_name', 'email')->get();
        $vehicleLocations = VehicleLocation::with([
            'vehicle:id,vehicle_number,vehicle_type',
            'location:id,name'
        ])->get();
        $routes = Route::with(['cities:id,name'])->get();

        return view('sales_reps.create', compact('users', 'vehicleLocations', 'routes'));
    }

    /**
     * Store a newly created sales representative.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'vehicle_location_id' => 'required|exists:vehicle_locations,id',
            'route_id' => 'required|exists:routes,id',
            'assigned_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check if user_id + route_id combination already exists
        $existingSalesRep = SalesRep::where('user_id', $request->user_id)
            ->where('route_id', $request->route_id)
            ->first();

        if ($existingSalesRep) {
            return redirect()->back()
                ->withErrors(['combination' => 'This user is already assigned to this route.'])
                ->withInput();
        }

        // Check if vehicle_location is valid
        $vehicleLocation = VehicleLocation::with(['vehicle', 'location'])->find($request->vehicle_location_id);
        if (!$vehicleLocation || !$vehicleLocation->vehicle || !$vehicleLocation->location) {
            return redirect()->back()
                ->withErrors(['vehicle_location_id' => 'Selected vehicle location is invalid.'])
                ->withInput();
        }

        DB::beginTransaction();

        try {
            SalesRep::create([
                'user_id' => $request->user_id,
                'vehicle_location_id' => $request->vehicle_location_id,
                'route_id' => $request->route_id,
                'assigned_date' => $request->assigned_date ?? now(),
                'status' => $request->status ?? 'active',
            ]);

            DB::commit();

            return redirect()->route('sales-reps.index')
                ->with('success', 'Sales representative created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create sales representative: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Display the specified sales representative.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $salesRep = SalesRep::with([
            'user:id,user_name,email',
            'vehicleLocation:id,vehicle_id,location_id',
            'vehicleLocation.vehicle:id,vehicle_number,vehicle_type',
            'vehicleLocation.location:id,name',
            'route:id,name',
            'route.cities:id,name,district,province',
            'targets'
        ])->find($id);

        if (!$salesRep) {
            abort(404, 'Sales representative not found');
        }

        return view('sales_reps.show', compact('salesRep'));
    }

    /**
     * Show the form for editing the specified sales representative.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $salesRep = SalesRep::with([
            'user:id,user_name,email',
            'vehicleLocation:id,vehicle_id,location_id',
            'route:id,name'
        ])->find($id);

        if (!$salesRep) {
            abort(404, 'Sales representative not found');
        }

        $users = User::select('id', 'user_name', 'email')->get();
        $vehicleLocations = VehicleLocation::with([
            'vehicle:id,vehicle_number,vehicle_type',
            'location:id,name'
        ])->get();
        $routes = Route::with(['cities:id,name'])->get();

        return view('sales_reps.edit', compact('salesRep', 'users', 'vehicleLocations', 'routes'));
    }

    /**
     * Update the specified sales representative.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $salesRep = SalesRep::find($id);

        if (!$salesRep) {
            abort(404, 'Sales representative not found');
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'vehicle_location_id' => 'required|exists:vehicle_locations,id',
            'route_id' => 'required|exists:routes,id',
            'assigned_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Check if user_id + route_id combination already exists (excluding current record)
        $existingSalesRep = SalesRep::where('user_id', $request->user_id)
            ->where('route_id', $request->route_id)
            ->where('id', '!=', $id)
            ->first();

        if ($existingSalesRep) {
            return redirect()->back()
                ->withErrors(['combination' => 'This user is already assigned to this route.'])
                ->withInput();
        }

        // Check if vehicle_location is valid
        $vehicleLocation = VehicleLocation::with(['vehicle', 'location'])->find($request->vehicle_location_id);
        if (!$vehicleLocation || !$vehicleLocation->vehicle || !$vehicleLocation->location) {
            return redirect()->back()
                ->withErrors(['vehicle_location_id' => 'Selected vehicle location is invalid.'])
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $salesRep->update([
                'user_id' => $request->user_id,
                'vehicle_location_id' => $request->vehicle_location_id,
                'route_id' => $request->route_id,
                'assigned_date' => $request->assigned_date ?? $salesRep->assigned_date,
                'status' => $request->status ?? $salesRep->status,
            ]);

            DB::commit();

            return redirect()->route('sales-reps.index')
                ->with('success', 'Sales representative updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update sales representative: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Remove the specified sales representative from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $salesRep = SalesRep::with(['user', 'targets'])->find($id);

            if (!$salesRep) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sales representative not found.',
                ], 404);
            }

            $userName = $salesRep->user->user_name ?? 'Unknown';

            // Delete associated targets first
            $salesRep->targets()->delete();

            // Delete the sales rep
            $salesRep->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Sales representative '{$userName}' and all associated targets deleted successfully.",
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sales representative: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get vehicle location details via AJAX.
     *
     * @param  int  $vehicleLocationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVehicleLocationDetails($vehicleLocationId)
    {
        try {
            $vehicleLocation = VehicleLocation::with([
                'vehicle:id,vehicle_number,vehicle_type',
                'location:id,name'
            ])->find($vehicleLocationId);

            if (!$vehicleLocation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vehicle location not found.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'vehicle' => $vehicleLocation->vehicle,
                    'location' => $vehicleLocation->location,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve vehicle location details.',
            ], 500);
        }
    }
}
