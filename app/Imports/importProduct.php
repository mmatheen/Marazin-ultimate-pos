<?php

namespace App\Imports;

use App\Models\Unit;
use App\Models\Batch;
use App\Models\Brand;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\MainCategory;
use App\Models\StockHistory;
use App\Models\LocationBatch;
use App\Models\ImeiNumber;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;


class importProduct implements ToCollection, WithHeadingRow
{
    private $data = [];
    private $validationErrors = []; // Array to store validation errors with row numbers
    private $currentRow = 1; // Track current row number (starting from 1 for header)
    private $importedProducts = []; // Track successfully imported products for rollback

    public function getData()
    {
        return $this->data;
    }

    public function getValidationErrors()
    {
        return $this->validationErrors;
    }

    // Increase max execution time for bulk import
    public function __construct()
    {
        ini_set('max_execution_time', 0);
        set_time_limit(0);
        $this->currentRow = 1; // Initialize row counter
    }

    public function collection(Collection $rows)
    {
        // First pass: Validate all rows without inserting anything
        $this->validateAllRows($rows);

        // If there are any validation errors, don't proceed with import
        if (!empty($this->validationErrors)) {
            Log::error("Import cancelled due to validation errors:", $this->validationErrors);
            return; // Stop processing - no data will be imported
        }

        // Second pass: Import all rows (only if validation passed)
        DB::beginTransaction();

        try {
            $processedCount = 0;
            foreach ($rows as $index => $row) {
                $excelRowNumber = $index + 2; // Excel row number (accounting for header)

                try {
                    $result = $this->processRow($row->toArray(), $excelRowNumber);
                    if ($result !== null) {
                        $processedCount++;
                    }
                } catch (\Exception $rowException) {
                    Log::error("Error processing row {$excelRowNumber}: " . $rowException->getMessage());
                    $this->validationErrors[] = "Row {$excelRowNumber}: " . $rowException->getMessage();
                    // Continue processing other rows instead of stopping entire import
                    continue;
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->validationErrors[] = "Import failed: " . $e->getMessage();
            Log::error("Import transaction failed: " . $e->getMessage());
            Log::error("Exception details: " . $e->getTraceAsString());
        }
    }

    /**
     * Convert various date formats to Y-m-d format
     */
    private function convertDateFormat($date, $rowNumber)
    {
        if (empty($date)) {
            return null;
        }

        // Remove any extra whitespace
        $date = trim($date);

        // If already in correct format, return as is
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Try to parse various date formats with exact matching
        $formats = [
            'Y/m/d',    // 2026/05/26
            'Y.m.d',    // 2026.05.09
            'Y-m-d',    // 2026-05-26
            'Y/n/j',    // 2026/5/9 (single digit month/day)
            'Y.n.j',    // 2026.5.9
            'Y-n-j',    // 2026-5-9
        ];

        foreach ($formats as $format) {
            $dateObj = \DateTime::createFromFormat($format, $date);
            if ($dateObj !== false) {
                // Additional validation to ensure the parsed date makes sense
                $reformatted = $dateObj->format($format);
                if ($reformatted === $date) {
                    return $dateObj->format('Y-m-d');
                }
            }
        }

        // Try regex-based parsing for common patterns
        if (preg_match('/^(\d{4})[\/\.-](\d{1,2})[\/\.-](\d{1,2})$/', $date, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];

            // Validate the date components
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        // If no format matches, try Carbon parse as last resort
        try {
            $carbonDate = \Carbon\Carbon::parse($date);
            return $carbonDate->format('Y-m-d');
        } catch (\Exception $e) {
            Log::error("Could not parse date '{$date}' in row {$rowNumber}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean row data to handle empty strings and whitespace
     */
    private function cleanRowData(array &$rowArray)
    {
        // Map Excel column headers to expected field names
        $this->mapExcelHeaders($rowArray);

        // Clean numeric fields - convert empty strings or whitespace to null
        $numericFields = [
            'stock_alert_quantity',
            'alert_quantity',
            'original_price',
            'retail_price',
            'whole_sale_price',
            'special_price',
            'max_retail_price',
            'qty',
            'pax',
            'is_imei_or_serial_no',
            'is_for_selling',
            'product_type'
        ];

        // Price fields that need special handling for Excel formulas
        $priceFields = [
            'original_price',
            'retail_price',
            'whole_sale_price',
            'special_price',
            'max_retail_price'
        ];

        foreach ($numericFields as $field) {
            if (isset($rowArray[$field])) {
                $value = trim($rowArray[$field]);
                // Only convert empty strings and pure whitespace to null, preserve "0"
                if ($value === '' || $value === ' ' || $value === '  ') {
                    $rowArray[$field] = null;
                } else {
                    // Special handling for price fields to clean Excel formula results
                    if (in_array($field, $priceFields) && $value !== null && $value !== '') {
                        // Remove any non-numeric characters except decimal point and minus sign
                        $cleanedValue = preg_replace('/[^0-9.\-]/', '', $value);

                        // Convert to float and round to 2 decimal places
                        if (is_numeric($cleanedValue)) {
                            $rowArray[$field] = round((float)$cleanedValue, 2);
                        } else {
                            $rowArray[$field] = $value; // Keep original if not numeric (will fail validation)
                        }
                    } else {
                        $rowArray[$field] = $value;
                    }
                }
            }
        }

        // Clean string fields - trim whitespace
        $stringFields = [
            'product_name',
            'sku',
            'unit_name',
            'brand_name',
            'main_category_name',
            'sub_category_name',
            'product_image_name',
            'description',
            'batch_no',
            'imei_serial_no',
            'product_type'
        ];

        foreach ($stringFields as $field) {
            if (isset($rowArray[$field])) {
                $rowArray[$field] = trim($rowArray[$field]);
                if ($rowArray[$field] === '') {
                    $rowArray[$field] = null;
                }
            }
        }
    }

    /**
     * Map Excel column headers to expected field names
     */
    private function mapExcelHeaders(array &$rowArray)
    {
        // Special handling for your Excel structure where "is_imeiserial_no" contains IMEI numbers
        if (isset($rowArray['is_imeiserial_no']) && !empty($rowArray['is_imeiserial_no'])) {
            // This column contains IMEI numbers, not 0/1 values
            // Set the IMEI flag to 1 and move the IMEI data to description if description is empty
            $imeiData = $rowArray['is_imeiserial_no'];

            // Set the flag to 1 since this product has IMEI numbers
            $rowArray['is_imei_or_serial_no'] = 1;

            // If description is empty, use the IMEI data as description
            // If description is not empty, keep existing description
            if (empty($rowArray['description'])) {
                $rowArray['description'] = $imeiData;
            }
        } else {
            // No IMEI data found, set flag to 0
            $rowArray['is_imei_or_serial_no'] = 0;
        }

        // Handle other standard mappings
        $headerMapping = [
            // Map Excel headers to expected field names
            'stock_alert' => 'stock_alert_quantity',  // Map "Stock Alert" column to stock_alert_quantity
            'is_for_selling' => 'is_for_selling',
            'product_type' => 'product_type',
            'pax' => 'pax'
        ];

        // Apply header mapping
        foreach ($headerMapping as $excelHeader => $expectedField) {
            if (isset($rowArray[$excelHeader])) {
                $rowArray[$expectedField] = $rowArray[$excelHeader];
                if ($excelHeader !== $expectedField) {
                    unset($rowArray[$excelHeader]);
                }
            }
        }
    }

    private function validateAllRows(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $excelRowNumber = $index + 2; // Excel row number (accounting for header)

            // Skip empty rows during validation
            if (empty(array_filter($rowArray))) {
                continue;
            }

            // Log the row data for debugging first few rows
            if ($excelRowNumber <= 5) {
                Log::info("Row {$excelRowNumber} data before cleaning:", $rowArray);
            }

            // Clean and validate data before processing
            $this->cleanRowData($rowArray);

            // Log the row data after cleaning for debugging first few rows
            if ($excelRowNumber <= 5) {
                Log::info("Row {$excelRowNumber} data after cleaning:", $rowArray);
            }

            // Convert expiry date to proper format if present
            if (!empty($rowArray['expiry_date'])) {
                $convertedDate = $this->convertDateFormat($rowArray['expiry_date'], $excelRowNumber);
                if ($convertedDate === false) {
                    $this->validationErrors[] = "Row {$excelRowNumber}: Invalid expiry date format '{$rowArray['expiry_date']}'. Expected formats: YYYY-MM-DD, YYYY/MM/DD, or YYYY.MM.DD";
                    continue;
                }
                $rowArray['expiry_date'] = $convertedDate;
            }

            // Validate IMEI numbers if provided - Check if is_imei_or_serial_no is 1 and extract from description
            $imeiFieldValue = null;
            $isImeiProduct = false;

            // Check if is_imei_or_serial_no is set to 1 (should be set by mapExcelHeaders)
            if (isset($rowArray['is_imei_or_serial_no'])) {
                $imeiFlag = trim($rowArray['is_imei_or_serial_no']);
                $isImeiProduct = ($imeiFlag == '1' || $imeiFlag == 1 || strtolower($imeiFlag) == 'true');
            }

            // For IMEI products, extract IMEI numbers from description
            if ($isImeiProduct && !empty($rowArray['description'])) {
                $imeiFieldValue = $rowArray['description'];
            }

            if (!empty($imeiFieldValue)) {
                $imeiList = preg_split('/[\/,]+/', $imeiFieldValue);
                $imeiList = array_map('trim', $imeiList);
                $imeiList = array_filter($imeiList);

                foreach ($imeiList as $imei) {
                    // Check IMEI format (must be 10-17 digits for flexibility as mentioned in requirements)
                    if (!preg_match('/^\d{10,17}$/', $imei)) {
                        $this->validationErrors[] = "Row {$excelRowNumber}: Invalid IMEI format '{$imei}'. IMEI must be 10-17 digits.";
                    } else {
                        // Check for duplicate IMEI
                        if (ImeiNumber::isDuplicate($imei)) {
                            $this->validationErrors[] = "Row {$excelRowNumber}: IMEI '{$imei}' already exists in the database.";
                        }
                    }
                }
            }

            $validator = Validator::make($rowArray, [
                'sku' => [
                    'nullable',
                    'regex:/^[a-zA-Z0-9\-]+$/',
                    'max:255',
                    Rule::unique('products', 'sku')
                ],
                'product_name' => 'required|string|max:255',
                'unit_name' => 'required|string|max:255',
                'brand_name' => 'nullable|string|max:255',
                'main_category_name' => 'nullable|string|max:255',
                'sub_category_name' => 'nullable|string|max:255',
                'stock_alert_quantity' => 'nullable|numeric|min:0',
                'original_price' => 'required|numeric|min:0',
                'retail_price' => 'required|numeric|min:0',
                'whole_sale_price' => 'required|numeric|min:0',
                'special_price' => 'nullable|numeric|min:0',
                'max_retail_price' => 'nullable|numeric|min:0',
                'qty' => 'nullable|numeric|min:0',
                'batch_no' => 'nullable|string|max:255',
                'expiry_date' => 'nullable|date_format:Y-m-d',
                'is_imei_or_serial_no' => 'nullable|integer|in:0,1',
                'is_for_selling' => 'nullable|integer|in:0,1',
                'product_type' => 'nullable|string|max:255',
                'pax' => 'nullable|numeric|min:0',
                'imei_serial_no' => 'nullable|string',
            ], [
                'sku.unique' => 'The SKU "' . ($rowArray['sku'] ?? 'N/A') . '" already exists. Please provide a unique SKU.',
                'sku.regex' => 'The SKU must contain only letters, numbers, and hyphens.',
                'product_name.required' => 'Product name is required.',
                'unit_name.required' => 'Unit name is required.',
                'stock_alert_quantity.numeric' => 'Stock alert quantity must be a valid number.',
                'original_price.required' => 'Original price is required.',
                'original_price.numeric' => 'Original price must be a valid number.',
                'retail_price.required' => 'Retail price is required.',
                'retail_price.numeric' => 'Retail price must be a valid number.',
                'whole_sale_price.required' => 'Wholesale price is required.',
                'whole_sale_price.numeric' => 'Wholesale price must be a valid number.',
                'expiry_date.date_format' => 'Expiry date must be in Y-m-d format (YYYY-MM-DD).',
                'expiry_date.date_format' => 'Expiry date must be in Y-m-d format (YYYY-MM-DD).',
                'is_imei_or_serial_no.integer' => 'IMEI/Serial number field must be 0 or 1.',
                'is_imei_or_serial_no.in' => 'IMEI/Serial number field must be 0 or 1.',
                'is_for_selling.integer' => 'Is for selling field must be 0 or 1.',
                'is_for_selling.in' => 'Is for selling field must be 0 or 1.',
                'product_type.string' => 'Product type must be a valid text value.',
                'product_type.max' => 'Product type must not exceed 255 characters.',
                'pax.numeric' => 'Pax must be a valid number.',
                'imei_serial_no.string' => 'IMEI/Serial number must be a valid string.',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->validationErrors[] = "Row {$excelRowNumber}: {$error}";
                }
                Log::error("Validation Errors for Row {$excelRowNumber}:", $validator->errors()->toArray());
            }
        }
    }

    private function processRow(array $row, int $excelRowNumber)
    {
        try {
            // Skip empty rows
            if (empty(array_filter($row))) {
                return null;
            }

            // Clean data before processing
            $this->cleanRowData($row);

            // Convert expiry date to proper format if present
            if (!empty($row['expiry_date'])) {
                $convertedDate = $this->convertDateFormat($row['expiry_date'], $excelRowNumber);
                if ($convertedDate !== false) {
                    $row['expiry_date'] = $convertedDate;
                }
            }

            // Generate SKU if not provided
            if (empty($row['sku'])) {
                $lastProduct = Product::whereRaw("sku REGEXP '^[0-9]+$'")->orderBy('id', 'desc')->first();
                if ($lastProduct && is_numeric($lastProduct->sku)) {
                    $lastSkuNumber = (int) $lastProduct->sku;
                } else {
                    $lastSkuNumber = 0;
                }
                $row['sku'] = str_pad($lastSkuNumber + 1, 4, '0', STR_PAD_LEFT);
            }

            // Get authenticated user's location for import
            $authUser = auth()->user();
            if (!$authUser) {
                throw new \Exception("User not authenticated.");
            }

            // First check if user has selected a specific location in session
            $selectedLocationId = session()->get('selected_location');

            if ($selectedLocationId) {
                // Verify that the selected location is assigned to the user
                $userLocationIds = $authUser->locations->pluck('id')->toArray();

                if (in_array($selectedLocationId, $userLocationIds)) {
                    $authLocationId = $selectedLocationId;
                } else {
                    throw new \Exception("Selected location {$selectedLocationId} is not assigned to the current user.");
                }
            } else {
                // Fall back to user's first assigned location
                $userLocations = $authUser->locations;
                if ($userLocations->isEmpty()) {
                    throw new \Exception("User has no assigned locations. Please assign at least one location to the user before importing products.");
                }
                $authLocationId = $userLocations->first()->id;
            }

            // Resolve or create related models
            $unit = Unit::firstOrCreate(
                ['name' => $row['unit_name']],
                ['location_id' => $authLocationId]
            );

            $brand = !empty($row['brand_name']) ?
                Brand::firstOrCreate(
                    ['name' => $row['brand_name']],
                    ['location_id' => $authLocationId]
                ) : null;

            $mainCategory = !empty($row['main_category_name']) ?
                MainCategory::firstOrCreate(
                    ['mainCategoryName' => $row['main_category_name']],
                    ['location_id' => $authLocationId]
                ) : null;

            $subCategory = !empty($row['sub_category_name']) && $mainCategory ?
                SubCategory::firstOrCreate([
                    'subCategoryname' => $row['sub_category_name'],
                    'main_category_id' => $mainCategory->id,
                ], ['location_id' => $authLocationId]) : null;

            // Check if product already exists (by ID or SKU) to prevent duplicates
            $product = null;

            // First, try to find by ID if provided in Excel
            if (!empty($row['id'])) {
                $product = Product::find($row['id']);
            }

            // If not found by ID, try to find by SKU to prevent duplicate SKUs
            if (!$product && !empty($row['sku'])) {
                $product = Product::where('sku', $row['sku'])->first();
            }

            // Prepare product data
            $productData = [
                'product_name' => $row['product_name'],
                'sku' => $row['sku'],
                'unit_id' => $unit->id,
                'brand_id' => $brand ? $brand->id : null,
                'main_category_id' => $mainCategory ? $mainCategory->id : null,
                'sub_category_id' => $subCategory ? $subCategory->id : null,
                'stock_alert' => 1,
                'alert_quantity' => $row['stock_alert_quantity'] ?? null,
                'product_image' => $row['product_image_name'] ?? null,
                'description' => $row['description'] ?? null,
                'is_imei_or_serial_no' => $row['is_imei_or_serial_no'] ?? 0,
                'is_for_selling' => $row['is_for_selling'] ?? null,
                'product_type' => $row['product_type'] ?? null,
                'pax' => $row['pax'] ?? null,
                'original_price' => $row['original_price'],
                'retail_price' => $row['retail_price'],
                'whole_sale_price' => $row['whole_sale_price'],
                'special_price' => $row['special_price'] ?? null,
                'max_retail_price' => $row['max_retail_price'] ?? null,
            ];

            // Update existing product or create new one
            if ($product) {
                // Update existing product
                $product->fill($productData);
                $product->save();
            } else {
                // Create new product
                $product = new Product($productData);
                $product->save();
            }

            $productId = $product->id;

            // Insert or update location_product table (prevent duplicate entries)
            $existingLocationProduct = DB::table('location_product')
                ->where('location_id', $authLocationId)
                ->where('product_id', $productId)
                ->first();

            if (!$existingLocationProduct) {
                DB::table('location_product')->insert([
                    'location_id' => $authLocationId,
                    'product_id' => $productId,
                    'qty' => 0, // Default quantity
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Handle Batch, LocationBatch, StockHistory - Check IMEI first to determine quantity
            $actualQty = $row['qty'] ?? 0;
            $imeiCount = 0;

            // Check for IMEI - use enhanced detection logic
            $imeiFieldValue = null;
            $isImeiProduct = false;

            // Check if is_imei_or_serial_no is set to 1 (should be set by mapExcelHeaders)
            if (isset($row['is_imei_or_serial_no'])) {
                $imeiFlag = trim($row['is_imei_or_serial_no']);
                $isImeiProduct = ($imeiFlag == '1' || $imeiFlag == 1 || strtolower($imeiFlag) == 'true');
            }

            // For IMEI products, extract IMEI numbers from description
            if ($isImeiProduct && !empty($row['description'])) {
                $imeiFieldValue = $row['description'];
            }

            // If IMEI numbers are provided, count them to determine actual quantity
            if (!empty($imeiFieldValue)) {
                $imeiList = preg_split('/[\/,]+/', $imeiFieldValue);
                $imeiList = array_map('trim', $imeiList);
                $imeiList = array_filter($imeiList); // Remove empty entries
                $imeiCount = count($imeiList);


                // If qty is 0 or not provided, use IMEI count as quantity
                if (empty($actualQty) || $actualQty == 0) {
                    $actualQty = $imeiCount;
                }

                // Validate that IMEI count matches quantity if both are provided
                if (!empty($row['qty']) && $row['qty'] > 0 && $imeiCount != $row['qty']) {
                    Log::warning("IMEI count ({$imeiCount}) doesn't match quantity ({$row['qty']}) for row {$excelRowNumber}. Using IMEI count as actual quantity.");
                    $actualQty = $imeiCount;
                }
            }

            // Create batch if we have quantity > 0 (either from qty field or IMEI count)
            if ($actualQty > 0) {
                $formattedExpiryDate = !empty($row['expiry_date']) ? $row['expiry_date'] : null;

                // Generate batch number if not provided
                $batchNo = $row['batch_no'] ?? Batch::generateNextBatchNo();

                // Create or update batch
                $batch = Batch::updateOrCreate(
                    [
                        'batch_no' => $batchNo,
                        'product_id' => $productId,
                    ],
                    [
                        'qty' => $actualQty,
                        'unit_cost' => $row['original_price'],
                        'retail_price' => $row['retail_price'],
                        'wholesale_price' => $row['whole_sale_price'],
                        'special_price' => $row['special_price'] ?? 0,
                        'max_retail_price' => $row['max_retail_price'] ?? 0,
                        'expiry_date' => $formattedExpiryDate,
                    ]
                );

                // Create or update location batch
                $locationBatch = LocationBatch::updateOrCreate(
                    [
                        'batch_id' => $batch->id,
                        'location_id' => $authLocationId,
                    ],
                    [
                        'qty' => $actualQty,
                    ]
                );

                // Create stock history entry
                StockHistory::updateOrCreate(
                    [
                        'loc_batch_id' => $locationBatch->id,
                        'stock_type' => StockHistory::STOCK_TYPE_OPENING,
                    ],
                    [
                        'quantity' => $actualQty,
                    ]
                );

                // Update location_product quantity
                DB::table('location_product')
                    ->where('location_id', $authLocationId)
                    ->where('product_id', $productId)
                    ->update(['qty' => $actualQty]);

            }

            // Handle IMEI/Serial Numbers - Use same enhanced detection logic
            $imeiFieldValue = null;
            $isImeiProduct = false;

            // Check if is_imei_or_serial_no is set to 1 (should be set by mapExcelHeaders)
            if (isset($row['is_imei_or_serial_no'])) {
                $imeiFlag = trim($row['is_imei_or_serial_no']);
                $isImeiProduct = ($imeiFlag == '1' || $imeiFlag == 1 || strtolower($imeiFlag) == 'true');
            }

            // For IMEI products, extract IMEI numbers from description
            if ($isImeiProduct && !empty($row['description'])) {
                $imeiFieldValue = $row['description'];
            }

            if (!empty($imeiFieldValue)) {

                // Ensure we have a batch for IMEI products (should be created above)
                if (!isset($batch)) {
                    // This should not happen with the new logic, but adding as fallback
                    $formattedExpiryDate = !empty($row['expiry_date']) ? $row['expiry_date'] : null;
                    $batchNo = $row['batch_no'] ?? Batch::generateNextBatchNo();

                    $batch = Batch::updateOrCreate(
                        [
                            'batch_no' => $batchNo,
                            'product_id' => $productId,
                        ],
                        [
                            'qty' => $imeiCount,
                            'unit_cost' => $row['original_price'],
                            'retail_price' => $row['retail_price'],
                            'wholesale_price' => $row['whole_sale_price'],
                            'special_price' => $row['special_price'] ?? 0,
                            'max_retail_price' => $row['max_retail_price'] ?? 0,
                            'expiry_date' => $formattedExpiryDate,
                        ]
                    );

                }

                // Split IMEIs by comma or slash and process each one
                $imeiList = preg_split('/[\/,]+/', $imeiFieldValue);
                $imeiList = array_map('trim', $imeiList);
                $imeiList = array_filter($imeiList); // Remove empty entries

                $validImeiCount = 0;
                foreach ($imeiList as $imei) {
                    // Check if IMEI is 10-17 digits (as per requirements)
                    if (preg_match('/^\d{10,17}$/', $imei)) {
                        // Check if IMEI already exists using the model method
                        if (!ImeiNumber::isDuplicate($imei)) {
                            try {
                                // Insert valid IMEI using the ImeiNumber model
                                $imeiRecord = ImeiNumber::create([
                                    'product_id' => $productId,
                                    'location_id' => $authLocationId,
                                    'batch_id' => $batch->id,
                                    'imei_number' => $imei,
                                    'status' => 'available'
                                ]);
                                $validImeiCount++;
                            } catch (\Exception $e) {
                                Log::error("Failed to create IMEI '{$imei}' for product {$productId} in row {$excelRowNumber}: " . $e->getMessage());
                            }
                        } else {
                            Log::warning("IMEI '{$imei}' already exists in database for Row {$excelRowNumber}");
                        }
                    } else {
                        Log::warning("Invalid IMEI format '{$imei}' in Row {$excelRowNumber}. IMEI must be 10-17 digits. Current length: " . strlen($imei));
                    }
                }

                // Update product to mark it as IMEI product if IMEIs were successfully added
                if ($validImeiCount > 0) {
                    $product->is_imei_or_serial_no = 1;
                    $product->save();
                }
            }
            // Store data for later use - only after successful product creation
            $this->data[] = array_merge($row, [
                'excel_row' => $excelRowNumber,
                'product_id' => $productId,
                'success' => true
            ]);

            return $product;

        } catch (\Exception $e) {
            throw new \Exception("Row {$excelRowNumber}: " . $e->getMessage());
        }
    }
}
