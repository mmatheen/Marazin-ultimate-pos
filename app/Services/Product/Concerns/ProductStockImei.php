<?php

namespace App\Services\Product\Concerns;

use App\Models\ImeiNumber;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

trait ProductStockImei
{
    public function getImeis(int $productId, Request $request): JsonResponse
    {
        $locationId = $request->input('location_id');

        $product = Product::find($productId);
        if (!$product) {
            return response()->json([
                'status' => 404,
                'message' => 'Product not found'
            ], 404);
        }

        $query = ImeiNumber::where('product_id', $productId)
            ->with(['location:id,name', 'batch:id,batch_no']);

        if ($locationId && $locationId !== '') {
            $query->where('location_id', $locationId);
        }

        $imeis = $query->get()->map(function ($imei) {
            return [
                'id' => $imei->id,
                'imei_number' => $imei->imei_number,
                'status' => $imei->status ?? 'available',
                'location_id' => $imei->location_id,
                'batch_id' => $imei->batch_id,
                'location_name' => $imei->location ? $imei->location->name : 'N/A',
                'batch_no' => $imei->batch ? $imei->batch->batch_no : 'N/A',
                'editable' => true,
            ];
        });

        return response()->json([
            'status' => 200,
            'data' => $imeis,
            'product_name' => $product->product_name
        ]);
    }

    public function deleteImeiById(int $id): void
    {
        $imei = ImeiNumber::findOrFail($id);
        $imei->delete();
    }

    public function updateSingleImeiNumber(int $id, string $newImei): void
    {
        if (ImeiNumber::where('imei_number', $newImei)->where('id', '!=', $id)->exists()) {
            throw new \RuntimeException('Duplicate IMEI: This IMEI is already associated with another product or sold.');
        }

        $imeiNumber = ImeiNumber::findOrFail($id);
        $imeiNumber->imei_number = $newImei;
        $imeiNumber->save();
    }

    public function respondUpdateSingleImei(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:imei_numbers,id',
            'new_imei' => 'required|string|max:255|unique:imei_numbers,imei_number,' . $request->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $this->updateSingleImeiNumber((int) $request->id, (string) $request->new_imei);

            return response()->json([
                'status' => 200,
                'message' => 'IMEI updated successfully.',
                'updated_imei' => $request->new_imei
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update IMEI: ' . $e->getMessage()
            ]);
        }
    }

    public function respondDeleteImei(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:imei_numbers,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $this->deleteImeiById((int) $request->id);

            return response()->json([
                'status' => 200,
                'message' => 'IMEI deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to delete IMEI: ' . $e->getMessage()
            ]);
        }
    }

    public function saveOrUpdateImei(Request $request): JsonResponse
    {
        Log::info('API saveOrUpdateImei called with data:', $request->all());

        $hasLocationId = $request->has('location_id') && $request->filled('location_id');
        $hasBatches = $request->has('batches');

        Log::info("API Detection: hasLocationId={$hasLocationId}, hasBatches={$hasBatches}");

        if ($hasLocationId) {
            Log::info('API Using new format (intelligent batch selection) - location_id present');
            return $this->saveOrUpdateImeiNewFormat($request);
        }

        if ($hasBatches && is_array($request->batches) && !empty($request->batches)) {
            $hasValidBatchStructure = isset($request->batches[0]['batch_id']) &&
                isset($request->batches[0]['location_id']);

            if ($hasValidBatchStructure) {
                Log::info('API Using old format (manual batch assignment) - valid batches structure');
                return $this->saveOrUpdateImeiOldFormat($request);
            }

            Log::warning('API Invalid batch structure, falling back to new format');
            return $this->saveOrUpdateImeiNewFormat($request);
        }

        Log::info('API Using new format (intelligent batch selection) - default fallback');
        return $this->saveOrUpdateImeiNewFormat($request);
    }

    private function saveOrUpdateImeiNewFormat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:locations,id',
            'imeis' => 'required|array|min:1',
            'imeis.*' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            Log::error('API Validation failed for new format:', $validator->messages()->toArray());
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $operation = 'save';
            $savedCount = 0;

            DB::transaction(function () use ($request, &$operation, &$savedCount) {
                $validImeis = collect($request->imeis)
                    ->filter(fn($imei) => $imei !== null && trim($imei) !== '')
                    ->values();

                Log::info("API Processing IMEIs for new format", [
                    'product_id' => $request->product_id,
                    'location_id' => $request->location_id,
                    'imeis' => $validImeis->toArray()
                ]);

                if ($validImeis->isEmpty()) {
                    throw new \Exception("No valid IMEI numbers provided.");
                }

                $batchAssignments = $this->getIntelligentBatchAssignments(
                    $request->product_id,
                    $request->location_id,
                    $validImeis->toArray()
                );

                Log::info("API Batch assignments calculated", ['assignments' => $batchAssignments]);

                if (empty($batchAssignments)) {
                    throw new \Exception("No available batches found for this product at the specified location.");
                }

                foreach ($batchAssignments as $assignment) {
                    $imei = $assignment['imei'];
                    $batchId = $assignment['batch_id'];

                    Log::info("API Processing IMEI assignment", [
                        'imei' => $imei,
                        'batch_id' => $batchId,
                        'batch_no' => $assignment['batch_no']
                    ]);

                    if (ImeiNumber::isDuplicate($imei)) {
                        Log::warning("API Duplicate IMEI detected", ['imei' => $imei]);
                        throw new \Exception("IMEI number $imei is already associated with another product or sold.");
                    }

                    $imeiModel = ImeiNumber::where([
                        'product_id' => $request->product_id,
                        'batch_id' => $batchId,
                        'location_id' => $request->location_id,
                        'imei_number' => $imei
                    ])->first();

                    if ($imeiModel) {
                        $operation = 'update';
                        $imeiModel->touch();
                        Log::info("API Updated existing IMEI", ['imei' => $imei, 'id' => $imeiModel->id]);
                    } else {
                        $operation = 'save';
                        $newImei = ImeiNumber::create([
                            'product_id' => $request->product_id,
                            'batch_id' => $batchId,
                            'location_id' => $request->location_id,
                            'imei_number' => $imei,
                            'status' => 'available',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $savedCount++;
                        Log::info("API Created new IMEI", [
                            'imei' => $imei,
                            'id' => $newImei->id,
                            'batch_id' => $batchId,
                            'product_id' => $request->product_id,
                            'location_id' => $request->location_id
                        ]);
                    }
                }

                Log::info("API Transaction completed", [
                    'total_processed' => count($batchAssignments),
                    'saved_count' => $savedCount,
                    'operation' => $operation
                ]);
            });

            $msg = $operation === 'update'
                ? 'IMEI numbers updated successfully'
                : 'IMEI numbers saved successfully';

            return response()->json([
                'status' => 200,
                'message' => $msg
            ]);
        } catch (\Exception $e) {
            Log::error('API IMEI save failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 500,
                'message' => 'Failed to save or update IMEIs: ' . $e->getMessage()
            ]);
        }
    }

    private function saveOrUpdateImeiOldFormat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'batches' => 'required|array',
            'batches.*.batch_id' => 'required|exists:batches,id',
            'batches.*.location_id' => 'required|exists:locations,id',
            'imeis' => 'nullable|array',
            'imeis.*' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        try {
            $operation = 'save';
            DB::transaction(function () use ($request, &$operation) {
                $validImeis = collect($request->imeis)
                    ->filter(fn($imei) => $imei !== null && trim($imei) !== '')
                    ->values();

                foreach ($request->batches as $batchInfo) {
                    $batchQty = (int) ($batchInfo['qty'] ?? 0);

                    if ($batchQty === 0 || $validImeis->isEmpty()) {
                        continue;
                    }

                    $assignedImeis = $validImeis->take($batchQty);
                    $validImeis = $validImeis->slice($assignedImeis->count());

                    foreach ($assignedImeis as $imei) {
                        if (ImeiNumber::isDuplicate($imei)) {
                            throw new \Exception("IMEI number $imei is already associated with another product or sold.");
                        }

                        $imeiModel = ImeiNumber::where([
                            'product_id' => $request->product_id,
                            'batch_id' => $batchInfo['batch_id'],
                            'location_id' => $batchInfo['location_id'],
                            'imei_number' => $imei
                        ])->first();

                        if ($imeiModel) {
                            $operation = 'update';
                            $imeiModel->touch();
                        } else {
                            $operation = 'save';
                            ImeiNumber::create([
                                'product_id' => $request->product_id,
                                'batch_id' => $batchInfo['batch_id'],
                                'location_id' => $batchInfo['location_id'],
                                'imei_number' => $imei,
                                'status' => 'available',
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            });

            $msg = $operation === 'update'
                ? 'IMEI numbers updated successfully (legacy format).'
                : 'IMEI numbers saved successfully (legacy format).';

            return response()->json([
                'status' => 200,
                'message' => $msg
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to save or update IMEIs: ' . $e->getMessage()
            ]);
        }
    }

    private function getIntelligentBatchAssignments($productId, $locationId, $imeis): array
    {
        Log::info("API Starting EXACT batch quantity based IMEI assignment", [
            'product_id' => $productId,
            'location_id' => $locationId,
            'imei_count' => count($imeis),
            'imeis' => $imeis
        ]);

        $batches = DB::table('location_batches')
            ->join('batches', 'location_batches.batch_id', '=', 'batches.id')
            ->where('batches.product_id', $productId)
            ->where('location_batches.location_id', $locationId)
            ->where('location_batches.qty', '>', 0)
            ->select(
                'batches.id as batch_id',
                'batches.batch_no',
                'location_batches.qty as available_qty',
                'batches.created_at'
            )
            ->orderBy('batches.id')
            ->get();

        Log::info("API Found batches with available quantity", [
            'batch_count' => $batches->count(),
            'batches' => $batches->toArray()
        ]);

        if ($batches->isEmpty()) {
            Log::error("API No batches with available quantity found", [
                'product_id' => $productId,
                'location_id' => $locationId
            ]);
            throw new \Exception("No batches with available quantity found for this product at the specified location. Please check stock levels.");
        }

        $assignments = [];
        $remainingImeis = $imeis;

        foreach ($batches as $batch) {
            if (empty($remainingImeis)) {
                break;
            }

            $existingAvailableImeiCount = ImeiNumber::where('product_id', $productId)
                ->where('batch_id', $batch->batch_id)
                ->where('location_id', $locationId)
                ->where('status', 'available')
                ->count();

            $exactAvailableCapacity = $batch->available_qty - $existingAvailableImeiCount;

            Log::info("API Batch capacity analysis", [
                'batch_id' => $batch->batch_id,
                'batch_no' => $batch->batch_no,
                'total_batch_qty' => $batch->available_qty,
                'existing_available_imei_count' => $existingAvailableImeiCount,
                'exact_available_capacity' => $exactAvailableCapacity
            ]);

            if ($exactAvailableCapacity <= 0) {
                Log::info("API Batch is full, moving to next batch", [
                    'batch_id' => $batch->batch_id,
                    'available_capacity' => $exactAvailableCapacity
                ]);
                continue;
            }

            $imeisToAssignCount = min($exactAvailableCapacity, count($remainingImeis));
            $imeisToAssign = array_slice($remainingImeis, 0, $imeisToAssignCount);

            Log::info("API EXACT assignment to batch", [
                'batch_id' => $batch->batch_id,
                'batch_no' => $batch->batch_no,
                'exact_capacity' => $exactAvailableCapacity,
                'imeis_to_assign_count' => $imeisToAssignCount,
                'imeis_to_assign' => $imeisToAssign
            ]);

            foreach ($imeisToAssign as $imei) {
                $assignments[] = [
                    'imei' => $imei,
                    'batch_id' => $batch->batch_id,
                    'batch_no' => $batch->batch_no
                ];
            }

            $remainingImeis = array_slice($remainingImeis, $imeisToAssignCount);

            Log::info("API Batch assignment completed", [
                'batch_id' => $batch->batch_id,
                'assigned_count' => $imeisToAssignCount,
                'remaining_imeis_count' => count($remainingImeis)
            ]);
        }

        if (!empty($remainingImeis)) {
            $unassignedCount = count($remainingImeis);
            $totalAvailableCapacity = $batches->sum(function ($batch) use ($productId, $locationId) {
                $existingAvailableCount = ImeiNumber::where('product_id', $productId)
                    ->where('batch_id', $batch->batch_id)
                    ->where('location_id', $locationId)
                    ->where('status', 'available')
                    ->count();
                return max(0, $batch->available_qty - $existingAvailableCount);
            });

            Log::error("API Insufficient total batch capacity", [
                'unassigned_count' => $unassignedCount,
                'total_available_capacity' => $totalAvailableCapacity,
                'unassigned_imeis' => $remainingImeis,
                'product_id' => $productId,
                'location_id' => $locationId
            ]);

            throw new \Exception("Insufficient batch capacity. Cannot assign {$unassignedCount} IMEI(s). Total available capacity: {$totalAvailableCapacity}. Please add more stock or use a different location.");
        }

        Log::info("API EXACT batch assignment completed successfully", [
            'total_assignments' => count($assignments),
            'assignments' => $assignments
        ]);

        return $assignments;
    }
}
