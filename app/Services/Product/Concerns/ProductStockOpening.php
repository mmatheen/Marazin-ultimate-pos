<?php

namespace App\Services\Product\Concerns;

use App\Models\Batch;
use App\Models\ImeiNumber;
use App\Models\Location;
use App\Models\LocationBatch;
use App\Models\Product;
use App\Models\StockHistory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

trait ProductStockOpening
{
    public function openingStockGetAll(): JsonResponse
    {
        $products = Product::with('locations')->get();

        $openingStock = [];

        foreach ($products as $product) {
            $batches = Batch::where('product_id', $product->id)
                ->whereHas('locationBatches.stockHistories', function ($query) {
                    $query->where('stock_type', StockHistory::STOCK_TYPE_OPENING);
                })
                ->with(['locationBatches.stockHistories' => function ($query) {
                    $query->where('stock_type', StockHistory::STOCK_TYPE_OPENING);
                }])
                ->get();

            $openingStock[] = [
                'products' => $products,
                'batches' => $batches->flatMap(function ($batch) {
                    return $batch->locationBatches->map(function ($locationBatch) use ($batch) {
                        return [
                            'batch_id' => $locationBatch->batch_id,
                            'location_id' => $locationBatch->location_id,
                            'quantity' => $locationBatch->qty,
                            'batch_no' => $batch->batch_no,
                            'expiry_date' => $batch->expiry_date,
                            'stock_histories' => $locationBatch->stockHistories->map(function ($stockHistory) {
                                return [
                                    'stock_history_id' => $stockHistory->id,
                                    'quantity' => $stockHistory->quantity,
                                    'stock_type' => $stockHistory->stock_type,
                                ];
                            })->values(),
                        ];
                    });
                })->values(),
            ];
        }

        return response()->json(['status' => 200, 'openingStock' => $openingStock], 200);
    }

    public function showOpeningStock(Request $request, $productId)
    {
        $product = Product::with(['locations', 'unit:id,name,short_name,allow_decimal'])->findOrFail($productId);
        $locations = $product->locations;

        $openingStock = [
            'batches' => [],
        ];

        if ($request->ajax() || $request->is('api/*')) {
            return response()->json([
                'status' => 200,
                'product' => $product,
                'locations' => $locations,
                'openingStock' => $openingStock
            ]);
        }

        return view('product.opening_stock', [
            'product' => $product,
            'locations' => $locations,
            'openingStock' => $openingStock,
            'editing' => false
        ]);
    }

    public function editOpeningStock(Request $request, $productId)
    {
        $product = Product::with(['locations', 'unit:id,name,short_name,allow_decimal'])->findOrFail($productId);
        $locations = $product->locations;

        $batches = Batch::where('product_id', $productId)
            ->whereHas('locationBatches.stockHistories', function ($query) {
                $query->where('stock_type', StockHistory::STOCK_TYPE_OPENING);
            })
            ->with(['locationBatches.stockHistories' => function ($query) {
                $query->where('stock_type', StockHistory::STOCK_TYPE_OPENING);
            }])
            ->get();

        $imeis = ImeiNumber::where('product_id', $productId)
            ->orderBy('id')
            ->pluck('imei_number', 'id');

        $allowDecimal = $product->unit && $product->unit->allow_decimal;

        $openingStock = [
            'product_id' => $product->id,
            'batches' => $batches->flatMap(function ($batch) use ($allowDecimal) {
                return $batch->locationBatches->map(function ($locationBatch) use ($batch, $allowDecimal) {
                    $location = Location::find($locationBatch->location_id);

                    $formattedQuantity = $allowDecimal
                        ? number_format((float)$locationBatch->qty, 2, '.', '')
                        : (int)$locationBatch->qty;

                    return [
                        'batch_id' => $locationBatch->batch_id,
                        'location_id' => $locationBatch->location_id,
                        'location_name' => $location->name,
                        'quantity' => $formattedQuantity,
                        'batch_no' => $batch->batch_no,
                        'expiry_date' => $batch->expiry_date,
                        'stock_histories' => $locationBatch->stockHistories->map(function ($stockHistory) {
                            return [
                                'stock_history_id' => $stockHistory->id,
                                'quantity' => $stockHistory->quantity,
                                'stock_type' => $stockHistory->stock_type,
                            ];
                        })->values(),
                    ];
                });
            })->values(),
            'imeis' => $imeis,
        ];

        if ($request->ajax() || $request->is('api/*')) {
            return response()->json(['status' => 200, 'product' => $product, 'locations' => $locations, 'openingStock' => $openingStock], 200);
        }

        return view('product.opening_stock', [
            'product' => $product,
            'locations' => $locations,
            'openingStock' => $openingStock,
            'editing' => true
        ]);
    }

    public function storeOrUpdateOpeningStock(Request $request, $productId)
    {
        $filteredLocations = array_filter($request->locations, function ($location) {
            return !empty($location['qty']);
        });

        foreach ($filteredLocations as &$location) {
            if (isset($location['expiry_date']) && ($location['expiry_date'] === '' || $location['expiry_date'] === 'null')) {
                $location['expiry_date'] = null;
            }
        }

        $validator = Validator::make(['locations' => $filteredLocations], [
            'locations' => 'required|array',
            'locations.*.id' => 'required|integer|exists:locations,id',
            'locations.*.qty' => 'required|numeric|min:1',
            'locations.*.unit_cost' => 'required|numeric|min:0',
            'locations.*.batch_no' => ['nullable', 'string', 'max:255'],
            'locations.*.expiry_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->messages()]);
        }

        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['status' => 404, 'message' => 'Product not found']);
        }

        try {
            $batchIds = [];
            $message = '';

            DB::transaction(function () use ($filteredLocations, $product, &$batchIds, &$message) {
                $isCreatingNew = true;

                $locationIds = array_column($filteredLocations, 'id');

                $existingLocationBatches = LocationBatch::whereHas('batch', function ($query) use ($product) {
                    $query->where('product_id', $product->id);
                })->get();

                foreach ($existingLocationBatches as $locationBatch) {
                    if (!in_array($locationBatch->location_id, $locationIds)) {
                        StockHistory::where('loc_batch_id', $locationBatch->id)
                            ->where('stock_type', StockHistory::STOCK_TYPE_OPENING)
                            ->delete();
                        $locationBatch->delete();
                    }
                }

                $batchIdsInUse = LocationBatch::whereIn('location_id', $locationIds)
                    ->whereHas('batch.product', fn($q) => $q->where('id', $product->id))
                    ->pluck('batch_id')->toArray();

                Batch::where('product_id', $product->id)
                    ->whereNotIn('id', $batchIdsInUse)
                    ->delete();

                foreach ($filteredLocations as $locationData) {
                    $formattedExpiryDate = $locationData['expiry_date']
                        ? Carbon::parse($locationData['expiry_date'])->format('Y-m-d')
                        : null;

                    $batch = Batch::updateOrCreate(
                        [
                            'batch_no' => $locationData['batch_no'] ?? Batch::generateNextBatchNo(),
                            'product_id' => $product->id,
                        ],
                        [
                            'qty' => $locationData['qty'],
                            'unit_cost' => $locationData['unit_cost'],
                            'wholesale_price' => $product->whole_sale_price,
                            'special_price' => $product->special_price ?? 0,
                            'retail_price' => $product->retail_price,
                            'max_retail_price' => $product->max_retail_price ?? 0,
                            'expiry_date' => $formattedExpiryDate,
                        ]
                    );

                    $locationBatch = LocationBatch::updateOrCreate(
                        [
                            'batch_id' => $batch->id,
                            'location_id' => $locationData['id'],
                        ],
                        [
                            'qty' => $locationData['qty'],
                        ]
                    );

                    $totalLocationQty = LocationBatch::where('location_id', $locationData['id'])
                        ->whereHas('batch', function ($query) use ($product) {
                            $query->where('product_id', $product->id);
                        })
                        ->sum('qty');

                    $product->locations()->updateExistingPivot($locationData['id'], ['qty' => $totalLocationQty]);

                    $stockHistory = StockHistory::where('loc_batch_id', $locationBatch->id)
                        ->where('stock_type', StockHistory::STOCK_TYPE_OPENING)
                        ->first();

                    if ($stockHistory) {
                        $isCreatingNew = false;
                        $stockHistory->update(['quantity' => $locationData['qty']]);
                    } else {
                        StockHistory::create([
                            'loc_batch_id' => $locationBatch->id,
                            'stock_type' => StockHistory::STOCK_TYPE_OPENING,
                            'quantity' => $locationData['qty'],
                        ]);
                    }

                    $batchIds[] = [
                        'batch_id' => $batch->id,
                        'location_id' => $locationData['id'],
                        'qty' => $locationData['qty'],
                    ];
                }

                $message = $isCreatingNew ? 'Opening Stock added successfully!' : 'Opening Stock updated successfully!';
            });

            return response()->json([
                'status' => 200,
                'message' => $message,
                'product' => $product,
                'batches' => $batchIds,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => 500, 'message' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function deleteOpeningStock($productId)
    {
        try {
            $product = Product::find($productId);
            if (!$product) {
                return response()->json(['status' => 404, 'message' => 'Product not found']);
            }

            $batchesWithOpeningStock = Batch::where('product_id', $product->id)
                ->whereHas('locationBatches.stockHistories', function ($query) {
                    $query->where('stock_type', StockHistory::STOCK_TYPE_OPENING);
                })
                ->with(['locationBatches.stockHistories'])
                ->get();

            $hasTransactions = false;
            $transactionDetails = [];

            foreach ($batchesWithOpeningStock as $batch) {
                if ($batch->salesProducts()->count() > 0) {
                    $hasTransactions = true;
                    $transactionDetails[] = 'Sales';
                }

                if ($batch->purchaseProducts()->count() > 0) {
                    $hasTransactions = true;
                    $transactionDetails[] = 'Purchases';
                }

                if ($batch->stockAdjustments()->count() > 0) {
                    $hasTransactions = true;
                    $transactionDetails[] = 'Stock Adjustments';
                }

                if ($batch->stockTransfers()->count() > 0) {
                    $hasTransactions = true;
                    $transactionDetails[] = 'Stock Transfers';
                }

                if ($batch->purchaseReturns()->count() > 0) {
                    $hasTransactions = true;
                    $transactionDetails[] = 'Purchase Returns';
                }

                if ($batch->saleReturns()->count() > 0) {
                    $hasTransactions = true;
                    $transactionDetails[] = 'Sale Returns';
                }

                foreach ($batch->locationBatches as $locationBatch) {
                    $nonOpeningStockHistories = $locationBatch->stockHistories
                        ->where('stock_type', '!=', StockHistory::STOCK_TYPE_OPENING);

                    if ($nonOpeningStockHistories->count() > 0) {
                        $hasTransactions = true;
                    }
                }
            }

            if ($hasTransactions) {
                $uniqueTransactions = array_unique($transactionDetails);
                $transactionList = implode(', ', $uniqueTransactions);
                return response()->json([
                    'status' => 403,
                    'message' => "Cannot delete opening stock! This opening stock has been used in transactions: {$transactionList}. Please adjust stock or contact administrator."
                ]);
            }

            DB::transaction(function () use ($product, $batchesWithOpeningStock) {

                foreach ($batchesWithOpeningStock as $batch) {
                    foreach ($batch->locationBatches as $locationBatch) {
                        $openingStockHistory = $locationBatch->stockHistories
                            ->where('stock_type', StockHistory::STOCK_TYPE_OPENING)
                            ->first();

                        if ($openingStockHistory) {
                            $openingQuantity = $openingStockHistory->quantity;

                            $openingStockHistory->delete();

                            $newQty = max(0, $locationBatch->qty - $openingQuantity);
                            $locationBatch->update(['qty' => $newQty]);

                            $totalLocationQty = LocationBatch::where('location_id', $locationBatch->location_id)
                                ->whereHas('batch', function ($query) use ($product) {
                                    $query->where('product_id', $product->id);
                                })
                                ->sum('qty');

                            $product->locations()->updateExistingPivot(
                                $locationBatch->location_id,
                                ['qty' => $totalLocationQty]
                            );

                            if ($newQty == 0 && $locationBatch->stockHistories()->count() == 0) {
                                $locationBatch->delete();
                            }
                        }
                    }

                    if ($batch->locationBatches()->count() == 0) {
                        $batch->delete();
                    }
                }
            });

            return response()->json([
                'status' => 200,
                'message' => 'Opening stock deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'An error occurred while deleting opening stock: ' . $e->getMessage()
            ]);
        }
    }

}
