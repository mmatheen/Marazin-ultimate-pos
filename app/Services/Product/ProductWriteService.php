<?php

namespace App\Services\Product;

use App\Http\Requests\Product\ApplyDiscountRequest;
use App\Http\Requests\Product\QuickAddProductRequest;
use App\Http\Requests\Product\SaveProductLocationsRequest;
use App\Http\Requests\Product\StoreOrUpdateProductRequest;
use App\Imports\importProduct;
use App\Models\Batch;
use App\Models\Brand;
use App\Models\LocationBatch;
use App\Models\MainCategory;
use App\Models\Product;
use App\Models\Discount;
use App\Models\StockHistory;
use App\Models\Unit;
use App\Services\TaxConfigurationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ProductWriteService
{
    public function productRules(?int $id = null): array
    {
        $rules = [
            'product_name' => 'required|string|max:255',
            'unit_id' => 'required|integer|exists:units,id',
            'brand_id' => 'required|integer|exists:brands,id',
            'main_category_id' => 'required|integer|exists:main_categories,id',
            'sub_category_id' => 'nullable|integer|exists:sub_categories,id',
            'locations' => 'required|array',
            'locations.*' => 'required|integer|exists:locations,id',
            'stock_alert' => 'nullable|boolean',
            'alert_quantity' => 'nullable|numeric|min:0',
            'product_image' => 'nullable|mimes:jpeg,png,jpg,gif|max:5120',
            'description' => 'nullable|string',
            'is_imei_or_serial_no' => 'nullable|boolean',
            'is_for_selling' => 'required|boolean',
            'product_type' => 'nullable|string',
            'pax' => 'nullable|integer',
            'retail_price' => 'required|numeric|min:0',
            'whole_sale_price' => 'required|numeric|min:0',
            'special_price' => 'nullable|numeric|min:0',
            'original_price' => 'required|numeric|min:0',
            'tax_percent' => 'nullable|numeric|min:0|max:100',
            'selling_price_tax_type' => 'nullable|in:inclusive,exclusive',
            'max_retail_price' => 'nullable|numeric|min:0',
        ];

        $rules['sku'] = $id
            ? 'nullable|string|unique:products,sku,' . $id
            : 'nullable|string|unique:products,sku';

        return $rules;
    }

    public function quickAddRules(): array
    {
        return [
            'sku' => 'required|string|unique:products,sku',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'stock_type' => 'required|in:unlimited,limited',
            'quantity' => 'required_if:stock_type,limited|nullable|numeric|min:0',
            'location_id' => 'required|exists:locations,id',
        ];
    }

    /**
     * @throws ModelNotFoundException
     * @throws UniqueConstraintViolationException
     */
    public function storeOrUpdate(Request $request, ?int $id = null): Product
    {
        return DB::transaction(function () use ($request, $id) {
            $product = $id
                ? Product::query()->lockForUpdate()->find($id)
                : new Product();

            if ($id && !$product) {
                throw new ModelNotFoundException('Product not found!');
            }

            $fileName = $this->storeProductImage($request);
            $data = $this->buildProductData($request, $product, $fileName);

            $sku = trim((string) $request->input('sku', ''));
            if ($sku !== '') {
                $product->fill(array_merge($data, ['sku' => $sku]));
                $product->save();
            } else {
                $product = $this->persistWithGeneratedSku($product, $data);
            }

            $product->locations()->sync($request->input('locations', []));

            return $product;
        });
    }

    public function quickAdd(Request $request): array
    {
        return DB::transaction(function () use ($request) {
            $categoryName = trim((string) $request->input('category', '')) ?: 'General';

            $mainCategory = MainCategory::withoutGlobalScopes()->firstOrCreate(
                ['mainCategoryName' => $categoryName],
                ['description' => 'Auto-created from POS Quick Add']
            );

            $brand = Brand::withoutGlobalScopes()->firstOrCreate(
                ['name' => 'General'],
                ['description' => 'Auto-created brand']
            );

            $unit = Unit::withoutGlobalScopes()->firstOrCreate(
                ['name' => 'Pieces'],
                [
                    'short_name' => 'PCS',
                    'allow_decimal' => 0,
                ]
            );

            $price = (float) $request->input('price');
            $quantity = (float) $request->input('quantity', 0);
            $isLimitedStock = $request->input('stock_type') === 'limited';

            $product = Product::create([
                'sku' => (string) $request->input('sku'),
                'product_name' => (string) $request->input('name'),
                'unit_id' => $unit->id,
                'brand_id' => $brand->id,
                'main_category_id' => $mainCategory->id,
                'stock_alert' => $isLimitedStock ? 1 : 0,
                'alert_quantity' => $isLimitedStock ? max(1, $quantity * 0.2) : null,
                'is_for_selling' => 0,
                'is_active' => 1,
                'retail_price' => $price,
                'whole_sale_price' => $price,
                'special_price' => $price,
                'original_price' => $price,
                'tax_percent' => TaxConfigurationService::defaultTaxPercent(),
                'selling_price_tax_type' => TaxConfigurationService::defaultSellingPriceTaxType(),
                'max_retail_price' => $price,
            ]);

            $locationId = (int) $request->input('location_id');
            $product->locations()->syncWithoutDetaching([$locationId]);

            $batchNo = null;
            if ($isLimitedStock) {
                $batchNo = 'QA-' . str_pad((string) $product->id, 6, '0', STR_PAD_LEFT);

                $batch = Batch::create([
                    'batch_no' => $batchNo,
                    'product_id' => $product->id,
                    'qty' => $quantity,
                    'unit_cost' => $price,
                    'wholesale_price' => $price,
                    'special_price' => $price,
                    'retail_price' => $price,
                    'max_retail_price' => $price,
                ]);

                $locationBatch = LocationBatch::create([
                    'batch_id' => $batch->id,
                    'location_id' => $locationId,
                    'qty' => $quantity,
                ]);

                StockHistory::create([
                    'loc_batch_id' => $locationBatch->id,
                    'stock_type' => StockHistory::STOCK_TYPE_OPENING,
                    'quantity' => $quantity,
                ]);
            }

            return [
                'product' => $product,
                'unit' => $unit,
                'batch_no' => $batchNo,
                'stock_quantity' => $isLimitedStock ? $quantity : 999999,
            ];
        });
    }

    /**
     * @throws ModelNotFoundException
     */
    public function toggleStatus(int $id): Product
    {
        $product = Product::find($id);

        if (!$product) {
            throw new ModelNotFoundException('Product not found!');
        }

        $product->is_active = !$product->is_active;
        $product->save();

        return $product;
    }

    public function applyDiscount(array $validated): Discount
    {
        return DB::transaction(function () use ($validated) {
            $discount = Discount::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'amount' => $validated['amount'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'apply_to_all' => false,
            ]);

            $discount->products()->attach($validated['product_ids'] ?? []);

            return $discount;
        });
    }

    public function syncProductLocationsPreservingStock(array $productIds, array $locationIds): void
    {
        $now = now();

        DB::transaction(function () use ($productIds, $locationIds, $now) {
            foreach ($productIds as $productId) {
                $existingLocationData = DB::table('location_product')
                    ->where('product_id', $productId)
                    ->get()
                    ->keyBy('location_id');

                $locationsToRemove = [];
                foreach ($existingLocationData as $locationId => $locationData) {
                    if (!in_array($locationId, $locationIds) && $locationData->qty == 0) {
                        $locationsToRemove[] = $locationId;
                    }
                }

                if (!empty($locationsToRemove)) {
                    DB::table('location_product')
                        ->where('product_id', $productId)
                        ->whereIn('location_id', $locationsToRemove)
                        ->delete();
                }

                foreach ($locationIds as $locationId) {
                    if (!isset($existingLocationData[$locationId])) {
                        DB::table('location_product')->insert([
                            'product_id' => $productId,
                            'location_id' => $locationId,
                            'qty' => 0,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]);
                    }
                }

                $batchIds = DB::table('batches')
                    ->where('product_id', $productId)
                    ->pluck('id')
                    ->toArray();

                foreach ($batchIds as $batchId) {
                    $existingBatchLocations = DB::table('location_batches')
                        ->where('batch_id', $batchId)
                        ->pluck('location_id')
                        ->toArray();

                    foreach ($locationIds as $locationId) {
                        if (!in_array($locationId, $existingBatchLocations) && !isset($existingLocationData[$locationId])) {
                            DB::table('location_batches')->insert([
                                'batch_id' => $batchId,
                                'location_id' => $locationId,
                                'qty' => 0,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                }
            }
        });
    }

    private function storeProductImage(Request $request): ?string
    {
        if (!$request->hasFile('product_image')) {
            return null;
        }

        $file = $request->file('product_image');
        $fileName = time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('/assets/images'), $fileName);

        return $fileName;
    }

    private function buildProductData(Request $request, Product $product, ?string $fileName): array
    {
        return [
            'product_name' => $request->input('product_name'),
            'unit_id' => $request->input('unit_id'),
            'brand_id' => $request->input('brand_id'),
            'main_category_id' => $request->input('main_category_id'),
            'sub_category_id' => $request->input('sub_category_id'),
            'stock_alert' => $request->has('stock_alert') ? ($request->boolean('stock_alert') ? 1 : 0) : 1,
            'alert_quantity' => $request->input('alert_quantity'),
            'product_image' => $fileName ?: $product->product_image,
            'description' => $request->input('description'),
            'is_imei_or_serial_no' => $request->input('is_imei_or_serial_no'),
            'is_for_selling' => $request->input('is_for_selling'),
            'product_type' => $request->input('product_type'),
            'pax' => $request->input('pax'),
            'retail_price' => $request->input('retail_price'),
            'whole_sale_price' => $request->input('whole_sale_price'),
            'special_price' => $request->input('special_price'),
            'original_price' => $request->input('original_price'),
            'tax_percent' => TaxConfigurationService::resolveTaxPercent($request->input('tax_percent')),
            'selling_price_tax_type' => TaxConfigurationService::resolveSellingPriceTaxType($request->input('selling_price_tax_type')),
            'max_retail_price' => $request->input('max_retail_price'),
        ];
    }

    private function persistWithGeneratedSku(Product $product, array $data): Product
    {
        $attempts = 0;

        do {
            $attempts++;

            try {
                $product->fill(array_merge($data, ['sku' => $this->generateNextNumericSku()]));
                $product->save();
                return $product;
            } catch (UniqueConstraintViolationException $e) {
                if ($attempts >= 3) {
                    throw $e;
                }
            }
        } while ($attempts < 3);

        return $product;
    }

    private function generateNextNumericSku(): string
    {
        $maxNumericSku = (int) Product::query()
            ->whereRaw("sku REGEXP '^[0-9]+$'")
            ->lockForUpdate()
            ->selectRaw('COALESCE(MAX(CAST(sku AS UNSIGNED)), 0) as max_sku')
            ->value('max_sku');

        return str_pad((string) ($maxNumericSku + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Delete a product when it has no blocking batch transactions.
     *
     * @return array{status: int, message: string, can_delete?: bool, used_in?: array, product_status?: string}
     */
    public function destroyProduct(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $product = Product::with([
                'batches.salesProducts',
                'batches.purchaseProducts',
                'batches.purchaseReturns',
                'batches.saleReturns',
                'batches.stockAdjustments',
                'batches.stockTransfers'
            ])->find($id);

            if (!$product) {
                return [
                    'status' => 404,
                    'message' => "No Such Product Found!"
                ];
            }

            $hasTransactions = false;
            $usedInTables = [];

            if ($product->batches->isNotEmpty()) {
                foreach ($product->batches as $batch) {
                    if ($batch->salesProducts->isNotEmpty()) {
                        $hasTransactions = true;
                        $usedInTables[] = 'Sales';
                    }
                    if ($batch->purchaseProducts->isNotEmpty()) {
                        $hasTransactions = true;
                        $usedInTables[] = 'Purchases';
                    }
                    if ($batch->purchaseReturns->isNotEmpty()) {
                        $hasTransactions = true;
                        $usedInTables[] = 'Purchase Returns';
                    }
                    if ($batch->saleReturns->isNotEmpty()) {
                        $hasTransactions = true;
                        $usedInTables[] = 'Sale Returns';
                    }
                    if ($batch->stockAdjustments->isNotEmpty()) {
                        $hasTransactions = true;
                        $usedInTables[] = 'Stock Adjustments';
                    }
                    if ($batch->stockTransfers->isNotEmpty()) {
                        $hasTransactions = true;
                        $usedInTables[] = 'Stock Transfers';
                    }
                }
            }

            if ($hasTransactions) {
                $usedInTables = array_unique($usedInTables);
                return [
                    'status' => 403,
                    'can_delete' => false,
                    'message' => "Cannot delete this product! It is being used in: " . implode(', ', $usedInTables) . ". Please deactivate the product instead.",
                    'used_in' => $usedInTables,
                    'product_status' => $product->is_active ? 'active' : 'inactive'
                ];
            }

            DB::table('location_product')->where('product_id', $id)->delete();

            if ($product->batches->isNotEmpty()) {
                $batchIds = $product->batches->pluck('id')->toArray();

                LocationBatch::whereIn('batch_id', $batchIds)->delete();

                Batch::whereIn('id', $batchIds)->delete();
            }

            DB::table('imei_numbers')->where('product_id', $id)->delete();

            DB::table('discount_product')->where('product_id', $id)->delete();

            $product->delete();

            return [
                'status' => 200,
                'can_delete' => true,
                'message' => "Product deleted successfully!"
            ];
        });
    }

    /**
     * @param  bool  $apiExtendedResponse  When true, match API JSON (counts, alternate messages, empty-rows branch).
     */
    public function respondImportProductsFromExcel(Request $request, bool $apiExtendedResponse = false): JsonResponse
    {
        $user = auth()->user();
        $selectedLocationId = $request->input('import_location');

        $userLocationIds = $user->locations->pluck('id')->toArray();
        if (!in_array($selectedLocationId, $userLocationIds)) {
            return response()->json([
                'status' => 403,
                'message' => 'You do not have access to the selected location.'
            ]);
        }

        session(['selected_location' => $selectedLocationId]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            if ($file->isValid()) {
                $import = new importProduct();

                Excel::import($import, $file);

                $validationErrors = $import->getValidationErrors();
                $records = $import->getData();
                $successCount = count($records);

                if (!empty($validationErrors)) {
                    if ($apiExtendedResponse) {
                        return response()->json([
                            'status' => 401,
                            'validation_errors' => $validationErrors,
                            'success_count' => $successCount,
                            'error_count' => count($validationErrors),
                        ]);
                    }

                    return response()->json([
                        'status' => 401,
                        'validation_errors' => $validationErrors,
                    ]);
                }

                if ($apiExtendedResponse && $successCount === 0) {
                    return response()->json([
                        'status' => 401,
                        'validation_errors' => ['No valid rows found in the Excel file. Please check that your file has data and follows the correct format.'],
                        'success_count' => 0,
                        'error_count' => 1,
                    ]);
                }

                if ($apiExtendedResponse) {
                    return response()->json([
                        'status' => 200,
                        'data' => $records,
                        'message' => "Import successful! {$successCount} products imported successfully!",
                        'success_count' => $successCount,
                        'error_count' => 0,
                    ]);
                }

                return response()->json([
                    'status' => 200,
                    'data' => $records,
                    'message' => "Import Products Excel file uploaded successfully!"
                ]);
            }

            return response()->json([
                'status' => 500,
                'message' => "File upload failed. Please try again."
            ]);
        }

        return response()->json([
            'status' => 400,
            'message' => "No file uploaded or file is invalid."
        ]);
    }

    public function respondSaveChanges(SaveProductLocationsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $productIds = $validated['product_ids'];
        $locationIds = $validated['location_ids'];

        try {
            $this->syncProductLocationsPreservingStock($productIds, $locationIds);
            return response()->json(['status' => 'success', 'message' => 'Locations added successfully. Existing location stock preserved.']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function respondApplyDiscount(ApplyDiscountRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $this->applyDiscount($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Discount applied successfully to selected products'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to apply discount: ' . $e->getMessage()
            ], 500);
        }
    }

    public function respondToggleStatus(int $id): JsonResponse
    {
        try {
            $product = $this->toggleStatus($id);

            $statusText = $product->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'status' => 200,
                'message' => "Product has been {$statusText} successfully!",
                'is_active' => $product->is_active
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => "Product not found!"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Failed to update product status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * API product toggle: same domain logic as {@see respondToggleStatus} but JSON uses `success` (not `status`).
     */
    public function respondToggleStatusForApi(int $id): JsonResponse
    {
        try {
            $product = $this->toggleStatus($id);
            $statusText = $product->is_active ? 'activated' : 'deactivated';

            return response()->json([
                'success' => true,
                'message' => "Product has been {$statusText} successfully!",
                'is_active' => $product->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function respondStoreOrUpdate(StoreOrUpdateProductRequest $request, ?int $id): JsonResponse
    {
        try {
            $product = $this->storeOrUpdate($request, $id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['status' => 404, 'message' => 'Product not found!']);
        } catch (UniqueConstraintViolationException $e) {
            Log::warning('Unique constraint violation while saving product', ['sku' => $request->sku ?? null, 'exception' => $e->getMessage()]);
            return response()->json([
                'status' => 400,
                'message' => 'A product with this SKU already exists. Please choose a different SKU or try again.',
                'errors' => ['sku' => 'SKU already exists']
            ]);
        } catch (\Exception $e) {
            Log::error('Error saving product', ['exception' => $e->getMessage()]);
            return response()->json(['status' => 500, 'message' => 'Failed to save product. Please try again.']);
        }

        app(ProductCacheService::class)->afterProductSaved(auth()->id());

        $message = $id ? 'Product Details Updated Successfully!' : 'New Product Details Created Successfully!';
        return response()->json(['status' => 200, 'message' => $message, 'product_id' => $product->id]);
    }

    public function respondQuickAdd(QuickAddProductRequest $request): JsonResponse
    {
        try {
            $result = $this->quickAdd($request);
            $product = $result['product'];
            $unit = $result['unit'];

            app(ProductCacheService::class)->afterQuickAdd(auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Product created successfully',
                'product' => [
                    'id' => $product->id,
                    'product_name' => $product->product_name,
                    'sku' => $product->sku,
                    'retail_price' => $product->retail_price,
                    'whole_sale_price' => $product->whole_sale_price,
                    'special_price' => $product->special_price,
                    'original_price' => $request->price,
                    'price' => $request->price,
                    'max_retail_price' => $product->max_retail_price,
                    'stock_alert' => $product->stock_alert,
                    'stock_quantity' => $result['stock_quantity'],
                    'batch_no' => $result['batch_no'],
                    'unit' => [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'short_name' => $unit->short_name,
                        'allow_decimal' => (bool) $unit->allow_decimal
                    ]
                ]
            ]);

        } catch (UniqueConstraintViolationException $e) {
            Log::warning('Quick add failed - SKU already exists', ['sku' => $request->sku]);
            return response()->json([
                'success' => false,
                'message' => 'A product with this SKU already exists in the system.'
            ], 409);
        } catch (\Exception $e) {
            Log::error('Quick add product failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function respondDestroyProduct(int $id): JsonResponse
    {
        try {
            $result = $this->destroyProduct($id);

            return response()->json($result, $result['status']);
        } catch (\Exception $e) {
            Log::error('Product deletion error: ' . $e->getMessage(), [
                'product_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 500,
                'message' => "Error deleting product: " . $e->getMessage()
            ], 500);
        }
    }
}
