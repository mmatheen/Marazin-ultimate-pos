<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesRepTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SalesRepTargetController extends Controller
{
    /**
     * Display a listing of sales rep targets.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $targets = SalesRepTarget::with([
            'salesRep.user:id,name,email',
            'salesRep.vehicle:id,vehicle_number',
            'salesRep.assignedLocation:id,name'
        ])
            ->select('id', 'sales_rep_id', 'target_amount', 'achieved_amount', 'target_month', 'created_at', 'updated_at')
            ->get();

        if ($targets->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No sales rep targets found.',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Sales rep targets retrieved successfully.',
            'data' => $targets,
        ], 200);
    }

    /**
     * Store a newly created sales rep target.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sales_rep_id' => 'required|exists:sales_reps,id',
            'target_amount' => 'required|numeric|min:0|max:99999999999999.99',
            'achieved_amount' => 'nullable|numeric|min:0|max:99999999999999.99',
            'target_month' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if target already exists for this sales rep and month
        $exists = SalesRepTarget::where('sales_rep_id', $request->sales_rep_id)
            ->where('target_month', $request->target_month)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Target already exists for this sales rep and month.',
                'errors' => ['combination' => ['Target for this month already exists.']],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $validatedData = $validator->validated();
            if (!isset($validatedData['achieved_amount'])) {
                $validatedData['achieved_amount'] = 0.00;
            }

            $target = SalesRepTarget::create($validatedData);

            // Load relationships for response
            $target->load([
                'salesRep.user:id,name,email',
                'salesRep.vehicle:id,vehicle_number',
                'salesRep.assignedLocation:id,name'
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales rep target created successfully.',
                'data' => $target,
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to create sales rep target.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified sales rep target.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $target = SalesRepTarget::with([
            'salesRep.user:id,name,email',
            'salesRep.vehicle:id,vehicle_number',
            'salesRep.assignedLocation:id,name'
        ])
            ->select('id', 'sales_rep_id', 'target_amount', 'achieved_amount', 'target_month', 'created_at', 'updated_at')
            ->find($id);

        if (!$target) {
            return response()->json([
                'status' => false,
                'message' => 'Sales rep target not found.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'Sales rep target retrieved successfully.',
            'data' => $target,
        ], 200);
    }

    /**
     * Update the specified sales rep target.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $target = SalesRepTarget::find($id);

        if (!$target) {
            return response()->json([
                'status' => false,
                'message' => 'Sales rep target not found.',
                'data' => null,
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'sales_rep_id' => 'required|exists:sales_reps,id',
            'target_amount' => 'required|numeric|min:0|max:99999999999999.99',
            'achieved_amount' => 'nullable|numeric|min:0|max:99999999999999.99',
            'target_month' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check if target already exists for this sales rep and month (excluding current record)
        $exists = SalesRepTarget::where('sales_rep_id', $request->sales_rep_id)
            ->where('target_month', $request->target_month)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Target already exists for this sales rep and month.',
                'errors' => ['combination' => ['Target for this month already exists.']],
            ], 422);
        }

        DB::beginTransaction();
        try {
            $validatedData = $validator->validated();
            if (!isset($validatedData['achieved_amount'])) {
                $validatedData['achieved_amount'] = $target->achieved_amount;
            }

            $target->update($validatedData);

            // Load relationships for response
            $target->load([
                'salesRep.user:id,name,email',
                'salesRep.vehicle:id,vehicle_number',
                'salesRep.assignedLocation:id,name'
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales rep target updated successfully.',
                'data' => $target->refresh(),
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to update sales rep target.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified sales rep target from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $target = SalesRepTarget::find($id);

        if (!$target) {
            return response()->json([
                'status' => false,
                'message' => 'Sales rep target not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $target->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Sales rep target deleted successfully.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'status' => false,
                'message' => 'Failed to delete sales rep target.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
